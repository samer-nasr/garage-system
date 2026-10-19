<?php
namespace App\Http\Controllers;

use App\Models\Listing;
use App\Models\OrderRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AdminController extends Controller
{
    public function loginForm() { return view('admin.login'); }
    public function login(Request $request)
    {
        $data=$request->validate(['email'=>'required|email','password'=>'required|string']);
        if (!Auth::attempt($data+['is_admin'=>true])) return back()->withErrors(['email'=>'Invalid administrator credentials.'])->onlyInput('email');
        $request->session()->regenerate();
        return redirect()->route('admin');
    }
    public function logout(Request $request)
    {
        Auth::logout(); $request->session()->invalidate(); $request->session()->regenerateToken();
        return redirect()->route('login');
    }
    public function index(Request $request)
    {
        $q=Listing::query();
        if (in_array($request->type,['part','car','service'])) $q->where('type',$request->type);
        if ($request->filled('q')) {
            $term=mb_substr($request->string('q'),0,100);
            $q->where(fn($q)=>$q->where('title->lt','like',"%{$term}%")->orWhere('part_number','like',"%{$term}%"));
        }
        return view('admin.index',['listings'=>$q->latest()->paginate(20)->withQueryString(),'orders'=>OrderRequest::latest()->paginate(15,['*'],'orders'),'counts'=>Listing::selectRaw('type, COUNT(*) as total')->groupBy('type')->pluck('total','type'),'newOrders'=>OrderRequest::where('status','new')->count()]);
    }
    public function edit(?Listing $listing=null) { return view('admin.edit',['listing'=>$listing ?? new Listing]); }
    public function save(Request $request, ?Listing $listing=null)
    {
        $data=$request->validate([
            'type'=>['required',Rule::in(['part','car','service'])],
            'title'=>'required|array:lt,en,ru','title.lt'=>'required|string|max:180','title.en'=>'nullable|string|max:180','title.ru'=>'nullable|string|max:180',
            'description'=>'required|array:lt,en,ru','description.*'=>'nullable|string|max:10000',
            'category'=>'nullable|string|max:100','make'=>'nullable|string|max:100','model'=>'nullable|string|max:100',
            'compatibility'=>'nullable|string|max:255','part_number'=>'nullable|string|max:100','oem_number'=>'nullable|string|max:100',
            'condition'=>['required',Rule::in(['new','used'])],'price'=>'nullable|numeric|min:0|max:999999.99',
            'stock'=>'required|integer|min:0|max:999999','year'=>'nullable|integer|min:1900|max:2100',
            'transmission'=>'nullable|string|max:100','fuel'=>'nullable|string|max:100',
            'images'=>'nullable|array|max:8','images.*'=>'image|mimes:jpg,jpeg,png,webp|max:5120',
            'is_active'=>'nullable|boolean','is_demo'=>'nullable|boolean','clear_images'=>'nullable|boolean',
        ]);
        $listing ??= new Listing;
        $images=$request->boolean('clear_images') ? [] : ($listing->images ?? []);
        if (count($images)+count($request->file('images',[]))>8) throw ValidationException::withMessages(['images'=>'Maximum eight photos per listing.']);
        foreach ($request->file('images',[]) as $image) $images[]=$image->store('listings','public');
        unset($data['clear_images']);
        $data['images']=$images;
        $data['is_active']=$request->boolean('is_active'); $data['is_demo']=$request->boolean('is_demo');
        $listing->fill($data)->save();
        return redirect()->route('admin.edit',$listing)->with('success','Listing saved.');
    }
    public function order(OrderRequest $order) { return view('admin.order',['order'=>$order->load('items')]); }
    public function updateOrder(Request $request, OrderRequest $order)
    {
        $data=$request->validate(['status'=>['required',Rule::in(['new','reviewing','awaiting_confirmation','confirmed','completed','cancelled'])],'delivery_quote'=>'nullable|numeric|min:0|max:99999.99']);
        DB::transaction(function () use ($data,$order) {
            $order=OrderRequest::whereKey($order->id)->lockForUpdate()->firstOrFail();
            if (in_array($data['status'],['confirmed','completed']) && !$order->stock_adjusted) {
                foreach ($order->items()->orderBy('listing_id')->get() as $item) {
                    $part=Listing::whereKey($item->listing_id)->lockForUpdate()->first();
                    if (!$part || $part->stock<$item->quantity) throw ValidationException::withMessages(['stock'=>'Insufficient stock for '.$item->title]);
                    $part->decrement('stock',$item->quantity);
                }
                $order->stock_adjusted=true;
            }
            if ($data['status']==='cancelled' && $order->stock_adjusted) {
                foreach ($order->items as $item) Listing::whereKey($item->listing_id)->increment('stock',$item->quantity);
                $order->stock_adjusted=false;
            }
            if ($order->stock_adjusted && !in_array($data['status'],['confirmed','completed','cancelled'])) throw ValidationException::withMessages(['status'=>'Cancel the confirmed order to restore stock before returning it to review.']);
            $order->fill($data)->save();
        });
        return back()->with('success','Order updated.');
    }
}
