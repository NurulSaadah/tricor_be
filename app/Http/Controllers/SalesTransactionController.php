<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Exception;
use Validator;
use App\Models\SalesTransaction;
use App\Models\Inventory;
use App\Models\SalesTransItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;

class SalesTransactionController extends Controller
{
    public function createNewSales(Request $request)
    {
      
        $validator = Validator::make($request->all(), [
            'client_name' => 'required',
            
        ]);
        if ($validator->fails()) { return response()->json(["message" => $validator->errors(), "code" => 422]); }


        $dataTransaction = [
            'client_name' => $request->client_name,
            'client_address' => $request->client_address,
            'client_contact_no' => $request->client_contact_no,
            'discount' => $request->discount,
            'created_by' => $request->created_by,
        ];

        //create new sales transaction
        $createTransaction = SalesTransaction::create($dataTransaction);
        
        foreach($request->lists as $item) {
            $data = array('sales_id' => $createTransaction->getKey(),
            'item_id' => $item['selectedItem'],
            'unit_price' => $item['unitPrice'],
            'quantity' => $item['quantity'],
            'total_excl_tax' => $item['total']);


            $salesitem= SalesTransItem::create($data);

            $getCogs = Inventory::select('cogs')->where('item_id',$item['selectedItem'])->first();
            $dataCogs =[
               'cogs' => $getCogs->cogs
            ];

            SalesTransItem::where('sales_item_id',$salesitem->getKey())->update($dataCogs);
        };

        //gettotal
        $getsum= SalesTransItem::where('sales_id',$createTransaction->getKey())
        ->sum('total_excl_tax');

        $getsumcogs= SalesTransItem::where('sales_id',$createTransaction->getKey())
        ->sum('cogs');
      
        //get total sst
        $sst = ($getsum - $request->discount )* 0.06;

        //get total + sst
        $totalinlsst = $getsum + $sst;


        $dataAmount = [
            'total_excl_tax' => $getsum,
            'total_tax' => $sst,
            'total_incl_tax' => $totalinlsst,
            'total_cogs' => $getsumcogs,
        ];
        $res = SalesTransaction::where('sales_id',$createTransaction->getKey())->update($dataAmount);

        if($res){
        return response()->json(["message" => "Record Successfully Created", "code" => 200]);
        }else{
            return response()->json(["message" => "Record Unsuccessfully Created", "code" => 400]);
        }
    }

    public function updateSales(Request $request)
    {
      
        $validator = Validator::make($request->all(), [
            'client_name' => 'required',
            
        ]);
        if ($validator->fails()) { return response()->json(["message" => $validator->errors(), "code" => 422]); }


        $dataTransaction = [
            'client_name' => $request->client_name,
            'client_address' => $request->client_address,
            'client_contact_no' => $request->client_contact_no,
            'discount' => $request->discount,
            'created_by' => $request->created_by,
        ];

        //create new sales transaction
        $createTransaction = SalesTransaction::where('sales_id',$request->sales_id)->update($dataTransaction);

        $delitem = SalesTransItem::where('sales_id',$request->sales_id)
        ->delete();
        
        foreach($request->lists as $item) {
            $data = array('sales_id' => $request->sales_id,
            'item_id' => $item['selectedItem'],
            'unit_price' => $item['unitPrice'],
            'quantity' => $item['quantity'],
            'total_excl_tax' => $item['total']);


            $salesitem= SalesTransItem::create($data);

            $getCogs = Inventory::select('cogs')->where('item_id',$item['selectedItem'])->first();
            $dataCogs =[
               'cogs' => $getCogs->cogs
            ];

            SalesTransItem::where('sales_item_id',$salesitem->getKey())->update($dataCogs);
        };

        //gettotal
        $getsum= SalesTransItem::where('sales_id',$request->sales_id)
        ->sum('total_excl_tax');

        $getsumcogs= SalesTransItem::where('sales_id',$request->sales_id)
        ->sum('cogs');
      
        //get total sst
        $sst = ($getsum - $request->discount )* 0.06;

        //get total + sst
        $totalinlsst = $getsum + $sst;


        $dataAmount = [
            'total_excl_tax' => $getsum,
            'total_tax' => $sst,
            'total_incl_tax' => $totalinlsst,
            'total_cogs' => $getsumcogs,
        ];
        $res = SalesTransaction::where('sales_id',$request->sales_id)->update($dataAmount);

        if($res){
        return response()->json(["message" => "Record Successfully Updated", "code" => 200]);
        }else{
            return response()->json(["message" => "Record Unsuccessfully Update", "code" => 400]);
        }
    }

    public function getSalesListbyStaffId(Request $request)
    {
        $salesList = DB::table('sales_transaction')
        ->select('sales_transaction.*','staff_management.name')
        ->leftJoin('staff_management','staff_management.staff_id','=','sales_transaction.created_by')
        ->where('sales_transaction.created_by',$request->staff_id)
        ->get();


        foreach($salesList as $item){
        
            $item->reference_no  =  strtoupper($item->reference_no) ?? '-';
            $item->created_at = $item->created_at ?? '-';
            $item->total_excl_tax = $item->total_excl_tax ?? '-';
            $item->tax_amount = $item->total_tax ?? '-';
            $item->total_incl_tax = $item->total_incl_tax ?? '-';
            $item->notes = $item->notes ?? '-';
            $item->remark = $item->remark ??'-';
            $item->name = strtoupper($item->name) ?? '-';
            
        }
        
       
        return response()->json(["message" => "List by staff ID :", 'list' => $salesList, "code" => 200]);
    }

    public function getMonthlySales(Request $request)
    {
       
        $result= DB::table('sales_transaction')
        ->select(DB::raw("DATE_FORMAT(created_at, '%M-%Y') as month"),DB::raw('SUM(total_excl_tax) as total_excl_tax'),
        DB::raw('SUM(total_tax) as total_tax'),DB::raw('SUM(total_incl_tax) as total_incl_tax'))
        ->where('created_by',$request->staff_id);

        if ($request->year !=""){
        $result->where(DB::raw("YEAR(created_at)"), $request->year);
        }
        if ($request->month !=""){
        $result->where(DB::raw("MONTH(created_at)"), $request->month);
        }
        $result->groupBy('month','created_by');
        $result->orderBy(DB::raw("MONTH(created_at)"),'asc');

        $res = $result->get();
        //1.
        $getsumexc= SalesTransaction::where('created_by',$request->staff_id);
        if ($request->year !=""){
            $getsumexc->where(DB::raw("YEAR(created_at)"), $request->year);
        }
        if ($request->month !=""){
            $getsumexc->where(DB::raw("MONTH(created_at)"), $request->month);
        }
        $getsumexcl=$getsumexc->sum('total_excl_tax');

        //2.
        $getsumtx= SalesTransaction::where('created_by',$request->staff_id);
        if ($request->year !=""){
            $getsumtx->where(DB::raw("YEAR(created_at)"), $request->year);
        }
        if ($request->month !=""){
            $getsumtx->where(DB::raw("MONTH(created_at)"), $request->month);
        }
        $getsumtax=$getsumtx->sum('total_tax');

        //3.
        $getsuminc= SalesTransaction::where('created_by',$request->staff_id);
        if ($request->year !=""){
            $getsuminc->where(DB::raw("YEAR(created_at)"), $request->year);
        }
        if ($request->month !=""){
            $getsuminc->where(DB::raw("MONTH(created_at)"), $request->month);
        }
        $getsumincl=$getsuminc->sum('total_incl_tax');

    
        return response()->json(["message" => "list:", 'list' => $res,'sumexcl' => $getsumexcl,'sumtax' =>$getsumtax,'sumincl' =>$getsumincl, "code" => 200]);
    }

    public function getMonthlySalesManager(Request $request)
    {
       
        $result= DB::table('sales_transaction')
        ->select('staff_management.name',DB::raw("DATE_FORMAT(sales_transaction.created_at, '%M-%Y') as month"),DB::raw('SUM(sales_transaction.total_excl_tax) as total_excl_tax'),
        DB::raw('SUM(sales_transaction.total_tax) as total_tax'),DB::raw('SUM(sales_transaction.total_incl_tax) as total_incl_tax'))

        ->leftjoin('staff_management','sales_transaction.created_by','=','staff_management.staff_id')
        ->where('staff_management.reporting_manager_id',$request->staff_id);

        if ($request->year !=""){
        $result->where(DB::raw("YEAR(sales_transaction.created_at)"), $request->year);
        }
        if ($request->month !=""){
        $result->where(DB::raw("MONTH(sales_transaction.created_at)"), $request->month);
        }

        $result->groupBy('month','sales_transaction.created_by');
        $result->orderBy(DB::raw("MONTH(sales_transaction.created_at)"),'asc');
        $res = $result->get();

        //1.get sum
        $getsum= DB::table('sales_transaction')
        ->select(DB::raw('SUM(sales_transaction.total_excl_tax) as total_excl_tax'),DB::raw('SUM(sales_transaction.total_tax) as total_tax'),
        DB::raw('SUM(sales_transaction.total_incl_tax) as total_incl_tax'))
        ->leftjoin('staff_management','sales_transaction.created_by','=','staff_management.staff_id')
        ->where('staff_management.reporting_manager_id',$request->staff_id);

        
        SalesTransaction::where('created_by',$request->staff_id);
        if ($request->year !=""){
            $getsum->where(DB::raw("YEAR(sales_transaction.created_at)"), $request->year);
        }

        if ($request->month !=""){
            $getsum->where(DB::raw("MONTH(sales_transaction.created_at)"), $request->month);
        }
        $getsumall=$getsum->first();


        foreach($res as $item){
        
            $item->name  =  strtoupper($item->name) ?? '-';
            $item->month = strtoupper($item->month) ?? '-';
            $item->total_excl_tax = $item->total_excl_tax ?? 0.00;
            $item->tax_amount = $item->total_tax ?? 0.00;
            $item->total_incl_tax = $item->total_incl_tax ?? 0.00;
            
        }
    
    
        return response()->json(["message" => "list:", 'list' => $res,'sumexcl' => $getsumall->total_excl_tax,'sumtax' =>$getsumall->total_tax,'sumincl' =>$getsumall->total_incl_tax, "code" => 200]);
    }

    public function getYearlySales(Request $request)
    {
       
        
        $staffId = $request->staff_id;
        $start_date = $request->start_date;
        $end_date = $request->end_date;
    

        $res = DB::select('CALL GetSalesSummary('.$staffId.',\''. $start_date.'\',\''. $end_date.'\')');
        foreach($res as $item){
        
            $item->name  =  strtoupper($item->name) ?? '-';
            $item->jan = $item->jan ?? 0.00;
            $item->feb = $item->feb ?? 0.00;
            $item->mar = $item->mar ?? 0.00;
            $item->apr = $item->apr ?? 0.00;
            $item->may = $item->may ?? 0.00;
            $item->jun = $item->jun ?? 0.00;
            $item->jul = $item->jul ?? 0.00;
            $item->aug = $item->aug ?? 0.00;
            $item->sept = $item->sept ?? 0.00;
            $item->oct = $item->oct ?? 0.00;
            $item->nov = $item->nov ?? 0.00;
            $item->december = $item->december ?? 0.00;
            
        }
       
        $resTotalExclTax = DB::select('CALL getTotalExclTax('.$staffId.',\''. $start_date.'\',\''. $end_date.'\')');

        foreach($resTotalExclTax  as $item2){
        
            $item2->totalexcljan = $item2->totalexcljan ?? 0.00;
            $item2->totalexclfeb = $item2->totalexclfeb ?? 0.00;
            $item2->totalexclmar = $item2->totalexclmar ?? 0.00;
            $item2->totalexclapr = $item2->totalexclapr ?? 0.00;
            $item2->totalexclmay = $item2->totalexclmay ?? 0.00;
            $item2->totalexcljun = $item2->totalexcljun ?? 0.00;
            $item2->totalexcljul = $item2->totalexcljul ?? 0.00;
            $item2->totalexclaug = $item2->totalexclaug ?? 0.00;
            $item2->totalexclsept = $item2->totalexclsept ?? 0.00;
            $item2->totalexcloct = $item2->totalexcloct ?? 0.00;
            $item2->totalexclnov = $item2->totalexclnov ?? 0.00;
            $item2->totalexcldec = $item2->totalexcldec ?? 0.00;
            
        }

        $resTotalTax = DB::select('CALL getTotalTax('.$staffId.',\''. $start_date.'\',\''. $end_date.'\')');

        foreach($resTotalTax  as $item3){
        
            $item3->totaltaxjan = $item3->totaltaxjan ?? 0.00;
            $item3->totaltaxfeb = $item3->totaltaxfeb ?? 0.00;
            $item3->totaltaxmar = $item3->totaltaxmar ?? 0.00;
            $item3->totaltaxlapr = $item3->totaltaxapr ?? 0.00;
            $item3->totaltaxmay = $item3->totaltaxmay ?? 0.00;
            $item3->totaltaxjun = $item3->totaltaxjun ?? 0.00;
            $item3->totaltaxjul = $item3->totaltaxjul ?? 0.00;
            $item3->totaltaxaug = $item3->totaltaxaug ?? 0.00;
            $item3->totaltaxsept = $item3->totaltaxsept ?? 0.00;
            $item3->totaltaxoct = $item3->totaltaxoct ?? 0.00;
            $item3->totaltaxnov = $item3->totaltaxnov ?? 0.00;
            $item3->totaltaxdec = $item3->totaltaxdec ?? 0.00;
            
        }

        $resTotalInclTax = DB::select('CALL getTotalInclTax('.$staffId.',\''. $start_date.'\',\''. $end_date.'\')');

        foreach($resTotalInclTax  as $item4){
        
            $item4->totalincljan = $item4->totalincljan ?? 0.00;
            $item4->totalinclfeb = $item4->totalinclfeb ?? 0.00;
            $item4->totalinclmar = $item4->totalinclmar ?? 0.00;
            $item4->totalincllapr = $item4->totalinclapr ?? 0.00;
            $item4->totalinclmay = $item4->totalinclmay ?? 0.00;
            $item4->totalincljun = $item4->totalincljun ?? 0.00;
            $item4->totalincljul = $item4->totalincljul ?? 0.00;
            $item4->totalinclaug = $item4->totalinclaug ?? 0.00;
            $item4->totalinclsept = $item4->totalinclsept ?? 0.00;
            $item4->totalincloct = $item4->totalincloct ?? 0.00;
            $item4->totalinclnov = $item4->totalinclnov ?? 0.00;
            $item4->totalincldec = $item4->totalincldec ?? 0.00;
            
        }
       
       
    
    
        return response()->json(["message" => "list:", 'list' => $res,'totalExclTax' =>$resTotalExclTax ,'totalTax' =>$resTotalTax ,'totalInclTax' =>$resTotalInclTax , "code" => 200]);
    }

    public function getSalesbyId(Request $request)
    {
        $salesList = DB::table('sales_transaction')
        ->select('client_name','client_address','client_contact_no','reference_no','discount')
        ->where('sales_id',$request->sales_id)
        ->first();

        $getItem =DB::table('sales_trans_item')
        ->select('sales_trans_item.item_id as selectedItem','inventory.item_name as selectedItem_name','sales_trans_item.unit_price as unitPrice',
        'general_setting.section_value as uom','sales_trans_item.quantity as quantity','sales_trans_item.total_excl_tax as total')
        ->leftJoin('inventory','inventory.item_id','=','sales_trans_item.item_id')
        ->leftJoin('general_setting','general_setting.id','=','inventory.uom_id')
        ->where('sales_trans_item.sales_id',$request->sales_id)
        ->get();

        foreach($getItem as $item){
        
            $item->unitPrice  =  $item->unitPrice ?? 0.00;
            $item->quantity = $item->quantity ?? 0.00;
            $item->total = $item->total ?? 0.00;
            $item->selectedItem_name = $item->selectedItem_name ?? '-';
            $item->discount =  $item->discount ?? 0.00;
            
        }



       
        return response()->json(["message" => "Sales :", 'list' => $salesList,'item'=> $getItem, "code" => 200]);
    }
}
