<?php
namespace App\Http\Controllers;

use App\Models\Listing;
use App\Models\OrderRequest;
use App\Models\CustomerInquiry;
use Illuminate\Support\Facades\Storage;
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
        if (!Auth::attempt($data+['is_admin'=>true])) return back()->withErrors(['email'=>__('admin.invalid_login')])->onlyInput('email');
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
        $q=$request->boolean('archived') ? Listing::onlyTrashed() : Listing::query();
        if (in_array($request->type,['part','car','service','sale','dismantling'])) $q->where('type',$request->type);
        if ($request->filled('q')) {
            $term=mb_substr($request->string('q'),0,100);
            $q->where(fn($q)=>$q->where('title->lt','like',"%{$term}%")->orWhere('title->en','like',"%{$term}%")->orWhere('title->ru','like',"%{$term}%")->orWhere('part_number','like',"%{$term}%"));
        }
        return view('admin.index',['listings'=>$q->latest()->paginate(20)->withQueryString(),'orders'=>OrderRequest::latest()->paginate(15,['*'],'orders'),'counts'=>Listing::selectRaw('type, COUNT(*) as total')->groupBy('type')->pluck('total','type'),'newOrders'=>OrderRequest::where('status','new')->count(),'inquiries'=>CustomerInquiry::latest()->paginate(15,['*'],'inquiries')]);
    }
    public function edit(?Listing $listing=null) { return view('admin.edit',['listing'=>$listing ?? new Listing]); }
    public function save(Request $request, ?Listing $listing=null)
    {
        $data=$request->validate([
            'type'=>['required',Rule::in(['part','car','service','sale','dismantling'])],
            'title'=>'required|array:lt,en,ru','title.lt'=>'required|string|max:180','title.en'=>'nullable|string|max:180','title.ru'=>'nullable|string|max:180',
            'description'=>'required|array:lt,en,ru','description.*'=>'nullable|string|max:10000',
            'category'=>'nullable|string|max:100','make'=>'nullable|string|max:100','model'=>'nullable|string|max:100',
            'compatibility'=>'nullable|string|max:255','part_number'=>'nullable|string|max:100','oem_number'=>'nullable|string|max:100',
            'condition'=>['required',Rule::in(['new','used'])],'price'=>'nullable|numeric|min:0|max:999999.99',
            'stock'=>'required|integer|min:0|max:999999','year'=>'nullable|integer|min:1900|max:2100',
            'transmission'=>'nullable|string|max:100','fuel'=>'nullable|string|max:100',
            'mileage'=>'nullable|integer|min:0|max:9999999','engine'=>'nullable|string|max:120',
            'vehicle_condition'=>['nullable',Rule::in(['excellent','good','needs_repair','damaged','non_runner'])],
            'availability'=>['required',Rule::in(['available','reserved','sold','unavailable','breaking'])],
            'fitments'=>'nullable|array|max:30','fitments.*.make'=>'nullable|string|max:100','fitments.*.model'=>'nullable|string|max:100',
            'fitments.*'=>'array:make,model,year_from,year_to',
            'fitments.*.year_from'=>'nullable|integer|min:1900|max:2100','fitments.*.year_to'=>'nullable|integer|min:1900|max:2100',
            'remove_images'=>'nullable|array','remove_images.*'=>'integer|min:0|max:7',
            'images'=>'nullable|array|max:8','images.*'=>'image|mimes:jpg,jpeg,png,webp|max:5120',
            'is_active'=>'nullable|boolean','is_demo'=>'nullable|boolean','clear_images'=>'nullable|boolean',
        ]);
        $listing ??= new Listing;
        $fitments = array_values(array_filter($data['fitments'] ?? [], fn (array $fitment): bool => count(array_filter($fitment,fn ($value): bool => $value!==null && $value!==''))>0));
        foreach ($fitments as $fitment) {
            if (empty($fitment['make']) || empty($fitment['model']) || empty($fitment['year_from']) || empty($fitment['year_to']) || $fitment['year_from']>$fitment['year_to']) {
                throw ValidationException::withMessages(['fitments'=>__('admin.fitment_error')]);
            }
        }
        $images=$request->boolean('clear_images') ? [] : array_values(array_diff_key($listing->images ?? [], array_flip($data['remove_images'] ?? [])));
        if (count($images)+count($request->file('images',[]))>8) throw ValidationException::withMessages(['images'=>__('admin.max_photos')]);
        $previousImages=$listing->images ?? [];
        $uploadedImages=[];
        foreach ($request->file('images',[]) as $image) {
            $path=$image->store('listings','public');
            $uploadedImages[]=$path;
            $images[]=$path;
        }
        unset($data['clear_images'],$data['fitments'],$data['remove_images']);
        $data['images']=$images;
        $data['is_active']=$request->boolean('is_active'); $data['is_demo']=$request->boolean('is_demo');
        try {
            DB::transaction(function () use ($listing,$data,$fitments): void {
                $listing->fill($data)->save();
                $listing->fitments()->delete();
                if ($listing->type==='part') { $listing->fitments()->createMany($fitments); }
            });
        } catch (\Throwable $exception) {
            Storage::disk('public')->delete($uploadedImages);
            throw $exception;
        }
        foreach (array_diff($previousImages,$images) as $removedImage) {
            if (str_starts_with($removedImage,'listings/') && !Listing::withTrashed()->whereJsonContains('images',$removedImage)->exists()) {
                Storage::disk('public')->delete($removedImage);
            }
        }
        return redirect()->route('admin.edit',$listing)->with('success',__('admin.saved'));
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
                    if (!$part || $part->stock<$item->quantity) throw ValidationException::withMessages(['stock'=>__('admin.insufficient_stock').' '.$item->title]);
                    $part->decrement('stock',$item->quantity);
                }
                $order->stock_adjusted=true;
            }
            if ($data['status']==='cancelled' && $order->stock_adjusted) {
                foreach ($order->items as $item) Listing::withTrashed()->whereKey($item->listing_id)->increment('stock',$item->quantity);
                $order->stock_adjusted=false;
            }
            if ($order->stock_adjusted && !in_array($data['status'],['confirmed','completed','cancelled'])) throw ValidationException::withMessages(['status'=>__('admin.cancel_first')]);
            $order->fill($data)->save();
        });
        return back()->with('success',__('admin.order_updated'));
    }

    public function archive(Listing $listing): \Illuminate\Http\RedirectResponse
    {
        $listing->delete();
        return redirect()->route('admin')->with('success',__('admin.archived_message'));
    }

    public function restore(int $id): \Illuminate\Http\RedirectResponse
    {
        Listing::onlyTrashed()->findOrFail($id)->restore();
        return back()->with('success',__('admin.restored'));
    }

    public function inquiry(CustomerInquiry $inquiry): \Illuminate\Contracts\View\View
    {
        return view('admin.inquiry',['inquiry'=>$inquiry->load('listing')]);
    }

    public function updateInquiry(Request $request, CustomerInquiry $inquiry): \Illuminate\Http\RedirectResponse
    {
        $inquiry->update($request->validate(['status'=>'required|in:new,reviewing,contacted,completed,cancelled']));
        return back()->with('success',__('admin.order_updated'));
    }

    public function photo(CustomerInquiry $inquiry, int $index): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $path=$inquiry->photos[$index] ?? null;
        abort_unless($path && Storage::disk('local')->exists($path),404);
        return Storage::disk('local')->response($path, null, ['Cache-Control'=>'private, no-store','X-Content-Type-Options'=>'nosniff']);
    }
}
