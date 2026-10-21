<?php

namespace App\Http\Controllers;

use App\Models\CustomerInquiry;
use App\Models\Listing;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Throwable;

class InquiryController extends Controller
{
    public function form(Request $request, string $locale, string $kind): View
    {
        $listing = $request->filled('listing') ? Listing::where('is_active', true)->findOrFail($request->integer('listing')) : null;
        abort_if($listing && ($kind==='sell-car' || !in_array($listing->type,['part','dismantling'])), 404);

        return view('inquiry', ['kind'=>$kind, 'listing'=>$listing]);
    }

    public function store(Request $request, string $locale, string $kind): RedirectResponse
    {
        $data = $request->validate([
            'name'=>'required|string|max:120', 'phone'=>'required|string|max:40', 'email'=>'nullable|email|max:180',
            'make'=>($kind==='sell-car'?'required':'nullable').'|string|max:100',
            'model'=>($kind==='sell-car'?'required':'nullable').'|string|max:100',
            'year'=>($kind==='sell-car'?'required':'nullable').'|integer|min:1900|max:'.(date('Y')+1),
            'mileage'=>($kind==='sell-car'?'required':'nullable').'|integer|min:0|max:9999999',
            'condition'=>['nullable', Rule::in(['excellent','good','needs_repair','damaged','non_runner'])],
            'asking_price'=>'nullable|numeric|min:0|max:9999999.99', 'oem_number'=>'nullable|string|max:100',
            'message'=>'required|string|max:5000', 'listing_id'=>'nullable|integer',
            'photos'=>'nullable|array|max:6', 'photos.*'=>'image|mimes:jpeg,jpg,png,webp|max:4096',
            'consent'=>'accepted', 'website'=>'nullable|size:0',
        ]);
        $listing = !empty($data['listing_id']) ? Listing::where('is_active',true)->findOrFail($data['listing_id']) : null;
        abort_if($kind==='sell-car' && $listing, 422);
        abort_if($listing && !in_array($listing->type,['part','dismantling']), 422);
        unset($data['consent'], $data['website'], $data['photos'], $data['listing_id']);
        $paths = [];

        try {
            foreach ($request->file('photos',[]) as $photo) {
                $paths[] = $photo->store('inquiries', 'local');
            }
            $inquiry = DB::transaction(fn (): CustomerInquiry => CustomerInquiry::create($data+[
                'type'=>$kind, 'locale'=>$locale, 'reference'=>(string) Str::uuid(), 'photos'=>$paths, 'listing_id'=>$listing?->id,
            ]));
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($paths);
            throw $exception;
        }

        $recipient = config('shop.notification_email');
        if (filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            try {
                Mail::raw(__('business.notification_body',['reference'=>$inquiry->reference]).' '.route('admin.inquiry',$inquiry), function ($mail) use ($recipient): void {
                    $mail->to($recipient)->subject(__('business.notification_subject'));
                });
                $inquiry->update(['notification_sent'=>true]);
            } catch (Throwable $exception) {
                Log::warning('Customer inquiry notification failed.', ['inquiry_id'=>$inquiry->id]);
            }
        }

        return redirect()->route('inquiry.thanks', $locale)->with('inquiry_reference',$inquiry->reference);
    }
}
