<?php

namespace Tests\Feature;

use App\Models\CustomerInquiry;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ExpandedBusinessTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->forceFill(['is_admin'=>true])->save();
        return $user;
    }

    private function listing(string $type='part'): Listing
    {
        return Listing::create(['type'=>$type,'title'=>['lt'=>'Bandomasis','en'=>'Test vehicle','ru'=>'Пример'],'description'=>['lt'=>'Aprašymas'],'price'=>100,'stock'=>3,'is_active'=>true]);
    }

    private function details(): array
    {
        return ['name'=>'Test Customer','phone'=>'+37060000000','make'=>'BMW','model'=>'3 Series','year'=>2018,'mileage'=>90000,'message'=>'Please review my request.','consent'=>1];
    }

    public function test_expanded_pages_and_messaging_render_in_three_languages(): void
    {
        foreach (['lt','en','ru'] as $locale) {
            foreach (['dismantling','cars-for-sale','request-part','sell-car'] as $section) {
                $this->get('/'.$locale.'/'.$section)->assertOk()->assertSee('WhatsApp')->assertSee('Viber')->assertDontSee('business.');
            }
        }
        foreach (['sale','dismantling'] as $type) {
            $listing=$this->listing($type);
            $this->get('/en/listing/'.$listing->id)->assertOk()->assertSee('https://wa.me/37065498007',false)->assertSee('viber://chat',false);
        }
        $this->get('/en/contact')->assertSee('Šiaurės g. 2')->assertSee('1L9PLWEzcf');
    }

    public function test_uploaded_photos_are_private_and_require_admin_access(): void
    {
        Storage::fake('local');
        Mail::fake();
        config(['shop.notification_email'=>'hello.nexusqart.com']);
        $this->post('/en/request-part',$this->details()+['photos'=>[UploadedFile::fake()->image('part.jpg')]])->assertSessionHasNoErrors()->assertRedirect('/en/inquiry-received');
        $inquiry=CustomerInquiry::firstOrFail();
        $this->assertSame('request-part',$inquiry->type);
        Storage::disk('local')->assertExists($inquiry->photos[0]);
        $this->assertFalse($inquiry->notification_sent);
        Mail::assertNothingSent();
        $url=route('admin.inquiry.photo',[$inquiry,0]);
        $this->get($url)->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create())->get($url)->assertForbidden();
        $this->actingAs($this->admin())->get($url)->assertOk()->assertHeader('X-Content-Type-Options','nosniff');
        $this->get(route('admin.inquiry.photo',[$inquiry,5]))->assertNotFound();
    }

    public function test_vehicle_offer_and_configurable_notification(): void
    {
        Mail::fake();
        config(['shop.notification_email'=>'test@example.com']);
        $this->post('/ru/sell-car',$this->details()+['asking_price'=>1234,'condition'=>'good'])->assertSessionHasNoErrors()->assertRedirect('/ru/inquiry-received');
        $inquiry=CustomerInquiry::firstOrFail();
        $this->assertSame('sell-car',$inquiry->type);
        $this->assertSame('ru',$inquiry->locale);
        $this->assertSame('1234.00',$inquiry->asking_price);
        $this->assertTrue($inquiry->notification_sent);
    }

    public function test_upload_validation_and_honeypot(): void
    {
        Storage::fake('local');
        $this->post('/en/request-part',$this->details()+['photos'=>[UploadedFile::fake()->create('bad.svg',1,'image/svg+xml')]])->assertSessionHasErrors('photos.0');
        $this->post('/en/request-part',$this->details()+['photos'=>[UploadedFile::fake()->image('large.jpg')->size(5000)]])->assertSessionHasErrors('photos.0');
        $this->post('/en/request-part',$this->details()+['website'=>'spam'])->assertSessionHasErrors('website');
        $this->assertDatabaseCount('customer_inquiries',0);
    }

    public function test_notification_failure_does_not_lose_customer_inquiry(): void
    {
        config(['shop.notification_email'=>'test@example.com']);
        Mail::shouldReceive('raw')->once()->andThrow(new \RuntimeException('Transport unavailable'));
        $this->post('/en/request-part',$this->details())->assertSessionHasNoErrors()->assertRedirect('/en/inquiry-received');
        $this->assertDatabaseCount('customer_inquiries',1);
        $this->assertFalse(CustomerInquiry::firstOrFail()->notification_sent);
    }

    public function test_compatibility_filters_match_one_complete_fitment_row(): void
    {
        $part=$this->listing();
        $part->fitments()->createMany([
            ['make'=>'BMW','model'=>'3 Series','year_from'=>2005,'year_to'=>2012],
            ['make'=>'Audi','model'=>'A4','year_from'=>2015,'year_to'=>2019],
        ]);
        $this->get('/en/parts?make=BMW&model=3%20Series&year=2010')->assertSee('Test vehicle');
        $this->get('/en/parts?make=BMW&model=A4&year=2010')->assertDontSee('Test vehicle');
        $this->get('/en/parts?make=BMW&year=2018')->assertDontSee('Test vehicle');
        $this->get('/en/parts?make=Audi&year=2018')->assertSee('Test vehicle');
    }

    public function test_translated_admin_can_edit_archive_and_restore_new_listing_types(): void
    {
        $this->actingAs($this->admin())->get('/admin')->assertOk()->assertSee('Valdymo skydelis');
        $this->post('/admin/language',['language'=>'ru'])->assertSessionHas('admin_locale','ru');
        $this->get('/admin')->assertSee('Панель управления');
        $this->post('/admin/language',['language'=>'en']);
        $listing=$this->listing('sale');
        $data=['type'=>'sale','title'=>['lt'=>'Automobilis','en'=>'Sale car'],'description'=>['lt'=>'Description'],'condition'=>'used','availability'=>'available','stock'=>1,'mileage'=>100000,'price'=>9000,'is_active'=>1];
        $this->put('/admin/listings/'.$listing->id,$data)->assertSessionHasNoErrors();
        $this->assertEquals(100000,$listing->fresh()->mileage);
        $this->delete('/admin/listings/'.$listing->id)->assertRedirect(route('admin'));
        $this->get('/en/listing/'.$listing->id)->assertNotFound();
        $this->get('/admin?archived=1')->assertOk()->assertSee('Sale car');
        $this->post('/admin/listings/'.$listing->id.'/restore')->assertSessionHasNoErrors();
        $this->get('/en/listing/'.$listing->id)->assertOk();
    }

    public function test_dismantling_inquiry_retains_specific_vehicle_reference(): void
    {
        $listing=$this->listing('dismantling');
        $this->post('/en/request-part',$this->details()+['listing_id'=>$listing->id])->assertSessionHasNoErrors();
        $this->assertEquals($listing->id,CustomerInquiry::firstOrFail()->listing_id);
    }

    public function test_archived_part_can_still_be_removed_from_an_existing_basket(): void
    {
        $part=$this->listing();
        $part->delete();
        $this->withSession(['basket'=>[$part->id=>1]])->get('/en/basket')->assertOk()->assertSee('Test vehicle')->assertSee('Remove');
        $this->put('/en/basket',['remove'=>$part->id])->assertSessionHas('basket',[]);
    }

    public function test_admin_fitment_save_and_individual_photo_removal(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('listings/one.jpg','test');
        Storage::disk('public')->put('listings/two.jpg','test');
        $part=$this->listing();
        $part->update(['images'=>['listings/one.jpg','listings/two.jpg']]);
        $this->actingAs($this->admin())->put('/admin/listings/'.$part->id,[
            'type'=>'part','title'=>['lt'=>'Dalies pavadinimas'],'description'=>['lt'=>'Description'],'condition'=>'used','availability'=>'available','stock'=>2,
            'remove_images'=>[0], 'fitments'=>[['make'=>'BMW','model'=>'3 Series','year_from'=>2005,'year_to'=>2012]],
        ])->assertSessionHasNoErrors();
        $this->assertSame(['listings/two.jpg'],$part->fresh()->images);
        Storage::disk('public')->assertMissing('listings/one.jpg');
        Storage::disk('public')->assertExists('listings/two.jpg');
        $this->assertSame(1,$part->fitments()->count());
    }
}
