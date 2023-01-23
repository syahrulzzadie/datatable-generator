<?php

namespace syahrulzzadie\DatatableGenerator;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class Generator
{
    protected static $draw = 0;
    protected static $index = false;
    protected static $columns = [];
    protected static $dataQuery = [];
    protected static $recordsTotal = 0;
    protected static $recordsFiltered = 0;
    // Export Options
    protected static $exportIndex = true;
    protected static $exportHeader = [];
    protected static $exportData = [];
    protected static $exportColumn = [];

    public static function create(Request $request, $query, $searchColumn = [], $defaultOrders = [])
    {
        self::$draw = $request->draw ? $request->draw : 0;
        $start = $request->start ? $request->start : 0;
        $length = $request->length ? $request->length : 10;

        $order = $request->order ? $request->order : false;
        $columnsOrder = $request->columns_order ? $request->columns_order : [];
        $searchText = $request->search ? $request->search['value'] : '';
        // Search Query
        if (strlen($searchText) > 0) {
            $query->where(function ($query) use ($searchColumn, $searchText) {
                foreach ($searchColumn as $i => $search) {
                    if ($i == 0) {
                        $query->where($search, 'like', '%' . $searchText . '%');
                    } else {
                        $query->orWhere($search, 'like', '%' . $searchText . '%');
                    }
                }
            });
        }
        // Ordering
        if (!is_bool($order) && $order != false && count($columnsOrder) > 0) {
            for ($i = 0; $i < count($order); $i++) {
                $orderIndex = $order[$i]['column'];
                $orderDirection = $order[$i]['dir'];
                $query->orderBy($columnsOrder[$orderIndex], $orderDirection);
            }
        } else {
            foreach ($defaultOrders as $keyOrder => $dirOrder) {
                $query->orderBy($keyOrder, $dirOrder);
            }
        }
        // Count All Data
        $dataTotal = $query->get();
        self::$recordsFiltered = count($dataTotal);
        // Pagination
        $query->offset($start);
        $query->limit($length);
        // Total Data
        $dataQuery = $query->get();
        self::$recordsTotal = count($dataQuery);
        self::$dataQuery = $dataQuery;
        return new self;
    }

    public static function indexColumn($isIndex = false)
    {
        self::$index = $isIndex;
        if (self::$index) {
            foreach (self::$dataQuery as $i => $item) {
                $item->DT_RowIndex = intval($i + 1);
            }
        }
        return new self;
    }

    public static function column($field, $value)
    {
        foreach (self::$dataQuery as $item) {
            $item->{$field} = $value($item);
        }
        return new self;
    }

    public static function toJson()
    {
        $data['draw'] = self::$draw;
        $data['data'] = self::$dataQuery;
        $data['recordsTotal'] = self::$recordsTotal;
        $data['recordsFiltered'] = self::$recordsFiltered;
        return response()->json($data);
    }
}
