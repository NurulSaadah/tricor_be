<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Exception;
use Validator;
use App\Models\Inventory;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
    public function getItemList()
    {
        $itemList = DB::table('inventory')
        ->select('inventory.item_id','inventory.item_name','inventory.uom_id','inventory.item_description',
        'inventory.unit_price','inventory.cogs','uom.section_value as uom',
        'inventory.status','general_setting.section_value as category','general_setting.id as category_id')
        ->leftJoin('general_setting','general_setting.id','=','inventory.category_id')
        ->leftJoin('general_setting as uom','uom.id','=','inventory.uom_id')
        ->orderBy('inventory.item_name','asc')
        ->get();

        foreach($itemList as $item){
        
            $item->item_name  =  strtoupper($item->item_name) ?? '-';
            $item->category = $item->category ?? '-';
            $item->description = $item->description ?? '-';
            $item->uom = $item->uom ?? '-';
            $item->cogs = number_format($item->cogs,2) ?? '-';
            $item->unit_price = number_format($item->unit_price,2) ?? '-';
            
            if($item->status == 0){
                $item->status = 'Enable'; 
            }
            if($item->status == 1){
                $item->status = 'Disable'; 
            }
            
        }
        return response()->json(["message" => "Staff List", 'list' => $itemList, "code" => 200]);
    }
    public function getItemListbyCategory(Request $request)
    {
        $itemList = DB::table('inventory')
        ->select('inventory.item_id','inventory.item_name','inventory.uom_id','inventory.item_description',
        'inventory.unit_price','inventory.cogs','uom.section_value as uom',
        'inventory.status','general_setting.section_value as category','general_setting.id as category_id')
        ->leftJoin('general_setting','general_setting.id','=','inventory.category_id')
        ->leftJoin('general_setting as uom','uom.id','=','inventory.uom_id')
        ->where('inventory.category_id','=',$request->category_id)
        ->orderBy('inventory.item_name','asc')
        ->get();

        foreach($itemList as $item){
        
            $item->item_name  =  strtoupper($item->item_name) ?? '-';
            $item->category = $item->category ?? '-';
            $item->description = $item->description ?? '-';
            $item->uom = $item->uom ?? '-';
            $item->cogs = $item->cogs ?? '-';
            
            if($item->status == 0){
                $item->status = 'Enable'; 
            }
            if($item->status == 1){
                $item->status = 'Disable'; 
            }
            
        }
        return response()->json(["message" => "Staff List", 'list' => $itemList, "code" => 200]);
    }
    public function getItembyId(Request $request)
    {
        $item = DB::table('inventory')
        ->select('inventory.item_id','inventory.item_name','inventory.uom_id','inventory.item_description',
        'inventory.unit_price','inventory.cogs','uom.section_value as uom',
        'inventory.status','general_setting.section_value as category','general_setting.id as category_id')
        ->leftJoin('general_setting','general_setting.id','=','inventory.category_id')
        ->leftJoin('general_setting as uom','uom.id','=','inventory.uom_id')
        ->where('inventory.item_id','=',$request->item_id)
        ->first();

        return response()->json(["message" => "Item List", 'list' => $item, "code" => 200]);
    }
    public function createNewItem(Request $request)
    {
      
        $validator = Validator::make($request->all(), [
            'category_id' => 'required',
            'item_name' => 'required',
            'unit_price' => 'required',
            'uom_id' => 'required',
            'cogs' => 'required',
        ]);
        if ($validator->fails()) { return response()->json(["message" => $validator->errors(), "code" => 422]); }


        $dataItem = [
            'item_name' => $request->item_name,
            'category_id' => $request->category_id,
            'uom_id' => $request->uom_id,
            'unit_price' => $request->unit_price,
            'cogs' => $request->cogs,
            'item_description' => $request->item_description,
            'status' => $request->status,
        ];

        if($request->item_id == 0){
           
          //1.add item 
          $createItem = Inventory::create($dataItem);

          
            if($createItem){
                return response()->json(["message" => "Record Successfully Created", "code" => 200]);
            }else{
                return response()->json(["message" => "Record unsuccessfully Created", "code" => 400]);
            }

        }else{
           
            $updateItem = Inventory::where('item_id',$request->item_id)->update($dataItem); 
        
            if($updateItem){
             return response()->json(["message" => "Record Successfully Updated", "code" => 200]);
            }else{
                return response()->json(["message" => "Record unsuccessfully Updated", "code" => 400]);
            }
            
          
        }

      

    }
}
