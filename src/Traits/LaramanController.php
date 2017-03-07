<?php

namespace Christhompsontldr\Laraman\Traits;

use Illuminate\Http\Request;
use DB;
use Illuminate\Support\Facades\View;
use Carbon\Carbon;
use Schema;

trait LaramanController
{
    //  array of column info
    public $columns = [];

    //  default sort column
    public $sort = 'id';

    //  default sort direction
    public $order = 'desc';

    //  the page
    public $page = 1;

    //  the limit
    public $limit = 10;

    //  the action buttons that will show
    public $buttons = [];

    //  show search
    public $searchEnabled = true;

    //  filters
    public $filters = [];

    public $limits = [10, 25, 50, 100];

    private function prep()
    {
        if (empty($this->model)) {
            $model = 'App\\' . str_singular(str_replace('Controller', '', class_basename($this)));
            $path  = strtolower(str_singular(str_replace('Controller', '', class_basename($this))));
        } else {
            $model = $this->model;
            $path  = strtolower(str_singular(str_replace('Controller', '', class_basename($this))));
        }

        $path = str_plural($path);

        //  route location
        $location = config('laraman.route.prefixDot') . $path;

        return [$model, $path, $location];
    }

    public function index(Request $request)
    {
        list($model, $path, $location) = $this->prep();

        $sort = $request->input('sort', $this->sort);
        $order = $request->input('order', $this->order);
        $page = $request->input('page', $this->page);
        $limit = $request->input('limit', $this->limit);
        $offset = $page * $limit - $limit;

        $columns = collect($this->columns);

        //  get the related model fields
        $related = $columns->filter(function ($column) {
            return str_contains($column['field'], '.');
        });

        //  remove related fields
        $fields = $columns->filter(function ($column) {
            return !str_contains($column['field'], '.');
        });

        $buttons = collect($this->buttons);

        $filters = collect($this->filters);

        $search = $request->input('search');

        $builder = $model::query();

        //  apply filters first
        $appliedFilters = [];
        collect($request->all())->each(function ($val, $key) use (&$builder, $filters, &$appliedFilters) {
            if (starts_with($key, 'filter_')) {
                $tmpKey = str_replace('filter_', '', $key);

                // ranges
                if (str_contains($val, ':')) {
                    list($start, $end) = explode(':', $val);

                    $start = urldecode($start);
                    $end   = urldecode($end);

                    //  datetime ranges
                    $filter = $filters->where('field', $tmpKey)->first();
                    if ($filter && $filter['type'] == 'datetime-range') {
                        $builder->whereBetween($tmpKey, [Carbon::parse($start)->format('Y-m-d'), Carbon::parse($end)->endOfDay()]);
                    } else {
                        $builder->whereBetween($tmpKey, [$start, $end]);
                    }

                    $appliedFilters[$key . '_start'] = $start;
                    $appliedFilters[$key . '_end'] = $end;
                }
                elseif ($filters->pluck('field')->contains($tmpKey)) {
                    $builder->where($tmpKey, $val);
                    $appliedFilters[$key] = $val;
                }
            }
        });

        $sortField = $sort;

        //  if sort is not related model
        //  let the db handle it
        if (str_contains($sort, '.')) {
            list($relatedModel, $rest) = explode('.', $sort);

            $builder->modelJoin($relatedModel);
        }
        //  if sort is on a count field, it's related
        elseif (!in_array($sort, Schema::getColumnListing($builder->getModel()->getTable()))) {
            $builder->withCount($sort)
                    ->with($sort);

            $sortField = $sort . '_count';
        }

        //  running a search
        if (!empty($search) && $this->searchEnabled) {
            $ids = $model::search($search)->take($model::count())->get()->pluck('id')->toArray();

            $builder->whereIn('id', $ids);

            //  user sorted results
            if ($request->has('sort')) {
                $builder->orderBy(DB::raw('`' . $sortField . '`'), $order);
            } else {
                $builder->orderByRaw(DB::raw('FIELD(id, ' . implode(', ', $ids)) . ') asc');
            }
        } else {
            $builder->orderBy(DB::raw('`' . $sortField . '`'), $order);
        }

        $paginator = $builder->paginate($limit);

        $rows = $paginator->map(function($row) use ($model, $fields, $related, $location, $buttons) {
            $new = [];

            //  run the formatters
            $fields->each(function ($column) use (&$new, $row, $model) {
                //  this will run the row through the accessors
                $new[$column['field']] = $row->{$column['field']};

                if (!empty($column['formatter'])) {
                    $params = [
                        'value'   => $new[$column['field']],
                        'column'  => $column,
                        'row'     => $row,
                        'options' => isset($column['options']) ? $column['options'] : []
                    ];

                    //  formatter is a string
                    if (is_string($column['formatter'])) {
                        $new[$column['field']] = $model::{'formatter' . title_case($column['formatter'])}($params);
                    }
                    // formatter is function
                    else {
                        $new[$column['field']] = call_user_func_array($column['formatter'], [$params]);
                    }
                }
            });

            //  related model fields
            $related->each(function ($column) use (&$new, $row, $model) {
                //  this will run the row through the accessors
                $new[$column['field']] = array_get($row, $column['field']);

                if (!empty($column['formatter'])) {
                    $new[$column['field']] = $model::{'formatter' . title_case($column['formatter'])}([
                        'value'   => $new[$column['field']],
                        'column'  => $column,
                        'row'     => $row,
                        'options' => isset($column['options']) ? $column['options'] : []
                    ]);
                }
            });


            $actions = [];

            $buttons->each(function($button) use (&$actions, $location, $row) {
                $class = '';

                if (is_array($button)) {
                    $class  = isset($button['class']) ? $button['class'] : null;
                    $button = isset($button['blade']) ? $button['blade'] : null;
                }

                //  blades
                if (strip_tags($button) == $button) {
                    $actions[] = view('laraman::buttons.' . $button, compact('row', 'location', 'class', 'location'))->render();
                }
                //  something else
                else {
                    $actions[] = $button;
                }
            });

            $new['actions'] = implode('&nbsp;', $actions);

            return (object) $new;
        });

        //  column clean up
        $columns = $columns->map(function ($column) {
            $column = (object) $column;

            //  always have a display
            if (!isset($column->display)) {
                $column->field = str_replace('-', ' ', $column->field);

                if ($column->field == 'id') {
                    $column->display = 'ID';
                } else {
                    $column->display = title_case($column->field);
                }
            }

            return $column;
        });

        //  filter clean up
        $filters = $filters->map(function ($filter) {
            $filter = (object) $filter;

            //  always have a display
            if (!isset($filter->display)) {
                $filter->field = str_replace('-', ' ', $filter->field);

                if ($filter->field == 'id') {
                    $filter->display = 'ID';
                } else {
                    $filter->display = title_case($filter->field);
                }
            }

            return $filter;
        });

        $params = array_merge(compact('sort', 'limit', 'page', 'order'), $appliedFilters);
        if ($this->searchEnabled && !empty($search)) {
            $params['search'] = $search;
        }

        return view('laraman::index', [
            'paginator' => $paginator,
            'rows' => collect($rows),
            'columns' => $columns,
            'params' => $params,
            'buttons' => $buttons,
            'filters' => $filters,
            'limits' => array_combine($this->limits, $this->limits),
            'search' => $search,
            'searchEnabled' => $this->searchEnabled,
            'location' => $location,
        ]);
    }

    /**
     * Show for the recordr
     *
     * @param integer $id
     */
    public function show($id)
    {
        list($model, $path, $location) = $this->prep();

        $row = $model::whereId($id)->first();

        if (empty($row)) {
            return redirect()->back()->with('error', 'Could not locate that row.');
        }

        $currentModel = new $model;

        $prev = $model::whereRaw('id = (select max(id) from ' . $currentModel->getTable() . ' where id < ' . $row->id . ')')->first();
        $next = $model::whereRaw('id = (select min(id) from ' . $currentModel->getTable() . ' where id > ' . $row->id . ')')->first();

        //  custom blade available
        $blade = 'laraman::' . $path . '.show';
        if (!View::exists('laraman::' . $blade)) {
            $blade = 'laraman::show';
        }

        $related = [];

        //  create a default
        if (!isset($this->related)) {
            $this->related = [];
        }

        foreach ($this->related as $relatedNamespace => $set) {
            $related[$relatedNamespace] = $set;

            //  get the related model fields
            $relatedFields = collect($set['fields'])->filter(function ($column) {
                if (!isset($column['field'])) {
                    $column = ['field' => $column];
                }

                return str_contains($column['field'], '.');
            });

            //  remove related fields
            $fields = collect($set['fields'])->filter(function ($column) {
                if (!isset($column['field'])) {
                    $column = ['field' => $column];
                }

                return !str_contains($column['field'], '.');
            });

            $buttons = collect(isset($set['buttons']) && is_array($set['buttons']) ? $set['buttons'] : []);

            $rows = $row->{$relatedNamespace}->map(function($relatedRow) use ($model, $set, $fields, $relatedFields, $buttons, $location) {
                $new = [];

                //  run the formatters
                $fields->each(function ($column) use (&$new, $relatedRow, $model) {
                    //  not assigned as array
                    if (!is_array($column)) {
                        $column = ['field' => $column];
                    }

                    //  this will run the row through the accessors
                    $new[$column['field']] = $relatedRow->{$column['field']};

                    if (!empty($column['formatter'])) {
                        $params = [
                            'value'   => $new[$column['field']],
                            'column'  => $column,
                            'row'     => $relatedRow,
                            'options' => isset($column['options']) ? $column['options'] : []
                        ];

                        //  formatter is a string
                        if (is_string($column['formatter'])) {
                            $new[$column['field']] = $model::{'formatter' . title_case($column['formatter'])}($params);
                        }
                        // formatter is function
                        else {
                            $new[$column['field']] = call_user_func_array($column['formatter'], [$params]);
                        }
                    }
                });

                //  related model fields
                $relatedFields->each(function ($column) use (&$new, $relatedRow, $model) {
                    //  this will run the row through the accessors
                    $new[$column['field']] = array_get($relatedRow, $column['field']);

                    if (!empty($column['formatter'])) {
                        $new[$column['field']] = $model::{'formatter' . title_case($column['formatter'])}([
                            'value'   => $new[$column['field']],
                            'column'  => $column,
                            'row'     => $relatedRow,
                            'options' => isset($column['options']) ? $column['options'] : []
                        ]);
                    }
                });


                $actions = [];

                $buttons->each(function($button) use (&$actions, $location, $relatedRow) {
                    $class = '';

                    if (is_array($button)) {
                        $class  = isset($button['class']) ? $button['class'] : null;
                        $button = isset($button['blade']) ? $button['blade'] : null;
                    }

                    //  blades
                    if (strip_tags($button) == $button) {
                        $actions[] = view('laraman::buttons.' . $button, compact('row', 'location', 'class', 'location'))->render();
                    }
                    //  something else
                    else {
                        $actions[] = $button;
                    }
                });

                $new['actions'] = implode('&nbsp;', $actions);

                return (object) $new;
            });

            //  column clean up
            $related[$relatedNamespace]['fields'] = collect($set['fields'])->map(function ($column) {
                //  not assigned as array
                if (!is_array($column)) {
                    $column = ['field' => $column];
                }

                $column = (object) $column;
                //  always have a display
                if (!isset($column->display)) {
                    $column->field = str_replace('-', ' ', $column->field);

                    if ($column->field == 'id') {
                        $column->display = 'ID';
                    } else {
                        $column->display = title_case($column->field);
                    }
                }

                return $column;
            });

            $related[$relatedNamespace]['rows'] = $rows;

            //  build Bootstrap Table json
            $json = [];

            $json['columns'] = collect($related[$relatedNamespace]['fields'])->map(function ($column) {
                $column->title = $column->display;
                unset($column->display);

                return (array) $column;
            })->toArray();

            $json['data'] = $rows->toArray();

            $related[$relatedNamespace]['json'] = json_encode($json);
        }

        return view($blade, compact('row', 'next', 'prev', 'location', 'related'));
    }

    public function destroy($id)
    {
        dd('destroying ' . $id);
    }

    public function filter(Request $request)
    {
        list($model, $path, $location) = $this->prep();

        //  sort and order not allowed
        $params = $request->only([/*'sort', 'order', 'page',*/ 'limit']);

        foreach ($this->filters as $filter) {
            if ($request->has('filter_' . $filter['field'])) {
                $params['filter_' . $filter['field']] = urlencode($request->input('filter_' . $filter['field']));
            }
            if (in_array($filter['type'], ['range', 'datetime-range'])) {
                if ($request->has('filter_' . $filter['field'] . '_start') && $request->has('filter_' . $filter['field'] . '_end')) {
                    $params['filter_' . $filter['field']] = urlencode($request->input('filter_' . $filter['field'] . '_start')) . ':' . urlencode($request->input('filter_' . $filter['field'] . '_end'));
                }
            }
        }

        //  reset to page 1
        $params['page'] = 1;

        $params['search'] = $request->input('search');

        return redirect()->route($location . '.index', str_replace('%3A', ':', http_build_query($params)));
    }

    public function search(Request $request)
    {
        if (!$this->searchEnabled) {
            return redirect()->back()->with('error', 'Search is disabled for this area.');
        }

        list($model, $path, $location) = $this->prep();

        //  sort and order not allowed
        $params = $request->only([/*'sort', 'order', 'page', */'limit']);

        foreach ($this->filters as $filter) {
            if ($request->has('filter_' . $filter['field'])) {
                $params['filter_' . $filter['field']] = urlencode($request->input('filter_' . $filter['field']));
            }
        }

        //  reset to page 1
        $params['page'] = 1;

        $params['search'] = trim($request->input('search'));

        return redirect()->route($location . '.index', http_build_query($params));
    }
}