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
    public $sort = null;

    //  default sort direction
    public $order = 'desc';

    //  the page
    public $page = 1;

    //  the limit
    public $limit;

    //  the action buttons that will show
    public $buttons = [];

    //  show search
    public $searchEnabled = null;

    //  filters
    public $filters = [];

    public $limits = [10, 25, 50, 100];

    public $viewPath;

    public $routePath;

    public $chunk = 1000;

    //  holds extras that will be passed from
    //  the controller __construct() to the view
    public $extras;

    private function startup()
    {
        if (empty($this->model)) {
            $this->model = 'App\\' . str_singular(str_replace('Controller', '', class_basename($this)));
        }

        //  turn on/off searching
        if (is_null($this->searchEnabled)) {
            $traits = class_uses($this->model);

            if (in_array('Laravel\Scout\Searchable', $traits)) {
                $this->searchEnabled = true;
            } else {
                $this->searchEnabled = false;
            }
        }

        if (is_null($this->viewPath)) {
            $this->viewPath = config('laraman.view.hintpath') . '::' . strtolower(str_plural(class_basename($this->model)));
        }

        if (is_null($this->routePath)) {
            $this->routePath = config('laraman.route.prefix') . '.' . strtolower(str_plural(class_basename($this->model)));
//            $this->routePath = str_replace('.index', '', Route::currentRouteName());
        }

        if (is_null($this->limit)) {
            $this->limit = config('laraman.limit');
        }
    }

    private function prep()
    {
        config(['laraman.route.path' => $this->routePath]);

        //  route location
        $location = $this->routePath;

        return [$this->model, $location];
    }

    public function create()
    {
        $this->startup();

        return view($this->viewPath . '.create');
    }

    public function index(Request $request)
    {
        $this->startup();

        list($model, $location) = $this->prep();

        $sort = $request->input('sort', $this->sort);
        $order = $request->input('order', $this->order);
        $page = (int) $request->input('page', $this->page);
        $limit = (int) $request->input('limit', $this->limit);
        $offset = (int) $page * $limit - $limit;

        //  convert everything to array
        foreach ($this->columns as &$column) {
            if (!is_array($column)) {
                $column = [
                    'field' => $column,
                ];
            }
        }

        $columns = collect($this->columns);

        //  if no sort, sort by first column
        if (!$sort) {
            $sort = $columns->first()['field'];
        }

        //  get the related model fields
        $related = $columns->filter(function ($column) {
            return str_contains($column['field'], '.');
        });

        //  remove related fields
        $fields = $columns->filter(function ($column) {
            return !str_contains($column['field'], '.');
        });

        //  count fields to eager load
        $counts = $columns->filter(function ($column) {
            return isset($column['formatter']) && $column['formatter'] == 'count';
        })->pluck('field');

        $buttons = collect($this->buttons);

        $filters = collect($this->filters);

        $search = $request->input('search');

        $builder = $model::query();

        //  scope it from the controller
        $builder = $this->scope($builder);

        //  apply filters first
        $appliedFilters = [];
        collect($request->input('filter'))->each(function ($val, $key) use (&$builder, $filters, &$appliedFilters) {
            $val = urldecode($val);

            //  this is modified if using a related model
//            $joinKey = $builder->getModel()->getTable() . '.' . $key;

            //  we need a join
            $relation = false;

            if (str_contains($key, '.')) {
                $relation = true;

                list($relatedModel, $rest) = explode('.', $key);
            }

            if ($filters->pluck('field')->contains($key)) {
                //  find this configured filter
                $filter = $filters->where('field', $key)->first();

                //  this model
                if (!$relation) {
                    //  custom model filter
                    if (method_exists($builder->getModel(), 'filter' . studly_case($key))) {
                        $builder = $builder->getModel()->{'filter' . studly_case($key)}($builder, $val);
                    }
                    //  ranges
                    elseif (str_contains($val, ':')) {
                        list($start, $end) = explode(':', $val);

                        $start = urldecode($start);
                        $end   = urldecode($end);

                        //  datetime ranges
                        $filter = $filters->where('field', $key)->first();
                        if ($filter && $filter['type'] == 'datetime-range') {
                            $builder->whereBetween($key, [Carbon::parse($start)->format('Y-m-d'), Carbon::parse($end)->endOfDay()]);
                        } else {
                            $builder->whereBetween($key, [$start, $end]);
                        }

                        $appliedFilters[$key . '-start'] = $start;
                        $appliedFilters[$key . '-end'] = $end;
                    }
                    elseif ($filter['type'] == 'input') {
                        $builder->where($key, 'like', '%' . $val . '%');
                    } else {
                        $builder->where($key, $val);
                    }
                }
                //  related model
                else {
                    $builder->whereHas($relatedModel, function ($query) use ($rest, $val, $filter, &$appliedFilters) {
                        if (method_exists($query->getModel(), 'filter' . studly_case($rest))) {
                            $query->{'filter' . studly_case($rest)}($query, $val);
                        }
                        //  ranges
                        elseif (str_contains($val, ':')) {
                            list($start, $end) = explode(':', $val);

                            $start = urldecode($start);
                            $end   = urldecode($end);

                            //  datetime ranges
                            $filter = $filters->where('field', $rest)->first();
                            if ($filter && $filter['type'] == 'datetime-range') {
                                $query->whereBetween($rest, [Carbon::parse($start)->format('Y-m-d'), Carbon::parse($end)->endOfDay()]);
                            } else {
                                $query->whereBetween($rest, [$start, $end]);
                            }

                            $appliedFilters[$key . '-start'] = $start;
                            $appliedFilters[$key . '-end'] = $end;
                        }
                        elseif ($filter['type'] == 'input') {
                            $query->where($rest, 'like', '%' . $val . '%');
                        } else {
                            $query->where($rest, $val);
                        }
                    });
                }

                $appliedFilters[$key] = $val;
            }
        });

        $tmpModel = new $model;

        //  eager load
        foreach ($counts as $count) {
            if (method_exists($tmpModel, $count)) {
                $builder->with($count);
            }
        }
        $related->each(function ($row) use ($builder) {
            $pieces = array_slice(explode('.', $row['field']), 0, -1);

            $builder->with(implode('.', $pieces));
        });

        //  related model, laraman has to do the heavy lifting
        if (str_contains($sort, '.')) {
            list($relatedModel, $rest) = explode('.', $sort);

            $builder->with($relatedModel);

        }

        //  running a search
        if (!empty($search) && $this->searchEnabled) {
            $results = $model::search($search)->take($model::count())->get();

            $ids = $results->pluck('id')->toArray();

            $builder->whereIn($builder->getModel()->getTable() . '.id', $ids);

            //  search sorted results
            if (!$request->has('sort') && count($ids) > 0) {
                $builder->orderByRaw(DB::raw('FIELD(' . $builder->getModel()->getTable() . '.id, ' . implode(', ', $ids)) . ') asc');

                $sort = null;
            }
        }

        //  build a faker paginator
        $paginator = $builder->paginate($limit);

        $key = $builder->getModel()->getKeyName();

        //  chunk the results and get just the data we need
        $tmpRows = collect([]);

        $builder->chunk($this->chunk, function ($rows) use (&$tmpRows, $key, $sort, $counts, $related) {
            foreach ($rows as $row) {
                $record = [];
                $record[$key] = $row->{$key};

                //  sort is on a `count` formatter field
                if ($counts->contains($sort)) {
                    $record[$sort . 'Count'] = $row->{$sort}->count();
                }

                $record['_laraman_sort'] = null;

                if (str_contains($sort, '.')) {
                    $relateIt = explode('.', $sort);

                    if ($row->{$relateIt[0]}) {
                        $record['_laraman_sort'] = $row->$relateIt[0]->$relateIt[1];
                    }
                } else {
                    $record['_laraman_sort'] = $row->{$sort};
                }

                $tmpRows->push($record);
            }
        });

        //  sort them only if not searching
        if ($sort) {
            $sortMethod = 'sortBy' . (($order == 'desc') ? 'Desc' : '');
            $tmpRows = $tmpRows->$sortMethod('_laraman_sort', SORT_NATURAL|SORT_FLAG_CASE);
        }

        //  slice them
        $ids = $tmpRows->slice($offset, $limit)->pluck($key);


        //  start a new builder for the real query
        $builder = $model::query();

        //  eager load
        $related->each(function ($row) use ($builder) {
            $pieces = array_slice(explode('.', $row['field']), 0, -1);

            $builder->with(implode('.', $pieces));
        });

        if ($ids->count() > 0) {
            $builder->whereIn($key, $ids)
                ->orderByRaw('FIELD(' . $key . ', ' . $ids->implode(',') . ')');
        } else {
            $builder->whereRaw('1 = 0');
        }

        $rows = $builder->get();

        $rows = $rows->map(function($row) use ($model, $fields, $related, $location, $buttons) {
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

            $new['entity'] = $row;

            return (object) $new;
        });

        //  column clean up
        $columns = $columns->map(function ($column) {
            $column = (object) $column;

            //  always have a display
            if (!isset($column->display)) {
                $column->display = str_replace('_', ' ', $column->field);

                if ($column->field == 'id') {
                    $column->display = 'ID';
                } else {
                    $column->display = title_case($column->display);
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

        $params = array_merge(compact('sort', 'limit', 'page', 'order'), ['filter' => $appliedFilters]);
        if ($this->searchEnabled && !empty($search)) {
            $params['search'] = $search;
        }

        if (empty($this->view)) {
            $this->view = config('laraman.view.hintpath') . '::index';
        }

        return view($this->view, [
            'paginator'     => $paginator,
            'rows'          => collect($rows),
            'columns'       => $columns,
            'params'        => $params,
            'buttons'       => $buttons,
            'filters'       => $filters,
            'limits'        => array_combine($this->limits, $this->limits),
            'search'        => $search,
            'searchEnabled' => $this->searchEnabled,
            'location'      => $location,
            'viewPath'      => $this->viewPath,
            'header'        => !empty($this->header) ? $this->header : $this->viewPath  .'.header',
            'footer'        => !empty($this->footer) ? $this->footer : $this->viewPath . '.footer',
            'extras'        => $this->extras,
        ]);
    }

    /**
     * Show for the recordr
     *
     * @param integer $id
     */
    public function show($id)
    {
        $this->startup();

        list($model, $location) = $this->prep();

        $row = $model::whereId($id)->first();

        if (empty($row)) {
            return redirect()->back()->with('error', 'Could not locate that row.');
        }

        $currentModel = new $model;

        $prev = $model::whereRaw('id = (select max(id) from ' . $currentModel->getTable() . ' where id < ' . $row->id . ')')->first();
        $next = $model::whereRaw('id = (select min(id) from ' . $currentModel->getTable() . ' where id > ' . $row->id . ')')->first();

        //  custom blade available
        $blade = $this->viewPath . '.show';
        if (!View::exists(config('laraman.view.hintpath') . '::' . $blade)) {
            $blade = config('laraman.view.hintpath') . '::show';
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
                        $actions[] = view(config('laraman.view.hintpath') . '::buttons.' . $button, compact('row', 'location', 'class', 'location'))->render();
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
        $this->startup();

        list($model, $location) = $this->prep();

        $row = $model::whereId($id)->first();

        if (!$row) {
            return back()
                ->with('error', 'Record with id ' . $id . ' is not valid.');
        }

        $row->delete();

        return redirect()
            ->route($location . '.index')
            ->with('success', 'Record with id ' . $id . ' deleted successfully.');
    }

    public function edit($id)
    {
        $this->startup();

        list($model, $location) = $this->prep();

        $row = $model::whereId($id)->first();

        if (!$row) {
            return redirect()->back()->with('error', 'Could not locate that row.');
        }

        $parts = explode('\\', $model);
        $name = strtolower(array_pop($parts));

        return view($this->viewPath . '.edit', [$name => $row]);
    }

    public function filter(Request $request)
    {
        $this->startup();

        list($model, $location) = $this->prep();

        //  sort and order not allowed
        $params = $request->only([/*'sort', 'order', 'page',*/ 'limit']);

        $submittedFilters = $request->input('filter');

        foreach ($this->filters as $filter) {
            if (in_array($filter['type'], ['range', 'datetime-range'])) {
                if (isset($submittedFilters[$filter['field'] . '-start']) && isset($submittedFilters[$filter['field'] . '-end'])) {
                    $params['filter[' . $filter['field'] . ']'] = urlencode($submittedFilters[$filter['field'] . '-start']) . ':' . urlencode($submittedFilters[$filter['field'] . '-end']);
                }
            }
            elseif (!empty($submittedFilters[$filter['field']])) {
                $params['filter[' . $filter['field'] . ']'] = urlencode($submittedFilters[$filter['field']]);
            }
        }

        //  reset to page 1
        $params['page'] = 1;

        $params['search'] = $request->input('search');

        return redirect()->route($location . '.index', str_replace('%3A', ':', http_build_query($params)));
    }

    /**
     * Allows the controller to scope results
     *
     * @param mixed $builder
     */
    public function scope($builder) {
        return $builder;
    }

    /**
     * Processes the search post and forwards it to a get
     *
     * @param Request $request
     */
    public function search(Request $request)
    {
        $this->startup();

        if (!$this->searchEnabled) {
            return redirect()->back()->with('error', 'Search is disabled for this area.');
        }

        list($model, $location) = $this->prep();

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