<?php
namespace App\Http\Controllers;

use App\Models\Listing;
use App\Models\OrderRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ShopController extends Controller
{
    public function home()
    {
        return view('home', ['parts'=>Listing::where('type','part')->where('is_active',true)->latest()->take(4)->get()]);
    }
    public function catalog(Request $request, string $locale, string $section)
    {
        $type = ['parts'=>'part','rentals'=>'car','services'=>'service'][$section] ?? abort(404);
        $filters = $request->validate(['q'=>'nullable|string|max:100','category'=>'nullable|string|max:100','make'=>'nullable|string|max:100','condition'=>['nullable',Rule::in(['new','used'])]]);
        $base = Listing::where('type',$type)->where('is_active',true);
        $categories = (clone $base)->whereNotNull('category')->distinct()->orderBy('category')->pluck('category');
        $makes = (clone $base)->whereNotNull('make')->distinct()->orderBy('make')->pluck('make');
        foreach (['category','make','condition'] as $key) if (!empty($filters[$key])) $base->where($key,$filters[$key]);
        if (!empty($filters['q'])) {
            $needle = '%'.str_replace(['%','_'], ['\\%','\\_'], $filters['q']).'%';
            $base->where(function ($q) use ($needle) {
                foreach (['title->lt','title->en','title->ru','part_number','oem_number','make','model','compatibility'] as $field) $q->orWhere($field,'like',$needle);
            });
        }
        return view('catalog', ['listings'=>$base->latest()->paginate(12)->withQueryString(),'section'=>$section,'categories'=>$categories,'makes'=>$makes]);
    }
    public function show(string $locale, Listing $listing)
    {
        abort_unless($listing->is_active,404);
        return view('listing', compact('listing'));
    }
    public function add(Request $request, string $locale, Listing $listing)
    {
        abort_unless($listing->type==='part' && $listing->is_active,404);
        $data = $request->validate(['quantity'=>'required|integer|min:1|max:999']);
        $basket = session('basket',[]);
        $quantity = ($basket[$listing->id] ?? 0) + $data['quantity'];
        if ($listing->price===null || $quantity>$listing->stock) return back()->withErrors(['basket'=>__('site.unavailable')]);
        $basket[$listing->id] = $quantity;
        session(['basket'=>$basket]);
        return back()->with('success',__('site.added'));
    }
    public function basket()
    {
        $basket = session('basket',[]);
        $listings = Listing::whereIn('id',array_keys($basket))->get();
        return view('basket',compact('basket','listings'));
    }
    public function updateBasket(Request $request)
    {
        if ($request->has('remove')) {
            $data = $request->validate(['remove'=>'required|integer|min:1']);
            $basket = session('basket',[]);
            unset($basket[$data['remove']]);
            session(['basket'=>$basket]);
            return back()->with('success',__('site.removed'));
        }
        $data = $request->validate(['quantities'=>'required|array','quantities.*'=>'required|integer|min:0|max:999']);
        $basket = session('basket',[]);
        foreach ($data['quantities'] as $id=>$quantity) if (array_key_exists($id,$basket)) {
            if ((int)$quantity===0) unset($basket[$id]); else $basket[$id]=(int)$quantity;
        }
        session(['basket'=>$basket]);
        return back()->with('success',__('site.updated'));
    }
    public function submit(Request $request)
    {
        $data = $request->validate([
            'name'=>'required|string|max:120','phone'=>'required|string|max:40','email'=>'nullable|email|max:180',
            'city'=>['required',Rule::in(['Vilnius'])],'address'=>'required|string|max:250','notes'=>'nullable|string|max:2000',
            'consent'=>'accepted',
        ]);
        $basket = session('basket',[]);
        if (!$basket) throw ValidationException::withMessages(['basket'=>__('site.empty')]);
        $order = DB::transaction(function () use ($data,$basket) {
            $listings = Listing::whereIn('id',array_keys($basket))->lockForUpdate()->get()->keyBy('id');
            $total = 0;
            $items = [];
            foreach ($basket as $id=>$quantity) {
                $part = $listings->get($id);
                if (!$part || !$part->is_active || $part->type!=='part' || $part->stock<$quantity || $part->price===null) throw ValidationException::withMessages(['basket'=>__('site.unavailable')]);
                $cents = (int) round((float)$part->price * 100);
                $total += $cents*$quantity;
                $items[] = ['listing_id'=>$id,'title'=>$part->translated('title'),'part_number'=>$part->part_number,'unit_price'=>$part->price,'quantity'=>$quantity];
            }
            unset($data['consent']);
            $order = OrderRequest::create($data+['locale'=>app()->getLocale(),'reference'=>(string) Str::uuid(),'subtotal'=>$total/100]);
            $order->items()->createMany($items);
            return $order;
        });
        session()->forget('basket');
        return redirect()->route('thanks',['locale'=>app()->getLocale()])->with('reference',$order->reference);
    }
}
