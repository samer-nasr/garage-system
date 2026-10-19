<?php
namespace Tests\Feature;

use App\Models\Listing;
use App\Models\OrderRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShopTest extends TestCase
{
    use RefreshDatabase;

    private function part(array $extra=[]): Listing
    {
        return Listing::create($extra+['type'=>'part','title'=>['lt'=>'Stabdžių diskas','en'=>'Brake disc','ru'=>'Тормозной диск'],'description'=>['lt'=>'Dalies aprašymas'],'price'=>25.50,'stock'=>4,'part_number'=>'ABC-123','is_active'=>true]);
    }
    private function customer(): array
    {
        return ['name'=>'Test Customer','phone'=>'+37060000000','email'=>'test@example.com','city'=>'Vilnius','address'=>'Test street 1','consent'=>'1'];
    }
    public function test_public_pages_render_in_all_languages(): void
    {
        foreach (['lt','en','ru'] as $locale) foreach (['','/parts','/rentals','/services','/contact','/privacy','/basket'] as $path) $this->get('/'.$locale.$path)->assertOk();
        $this->get('/de')->assertNotFound();
        $this->get('/en')->assertSee('Everything your');
        $this->get('/lt')->assertSee('Viskas jūsų');
        $this->get('/ru')->assertSee('Всё для вашего');
    }
    public function test_part_search_and_lithuanian_fallback(): void
    {
        $part=$this->part();
        $this->get('/en/parts?q=ABC-123')->assertOk()->assertSee('Brake disc');
        $this->get('/en/parts?q=missing')->assertDontSee('Brake disc');
        $this->get('/en/listing/'.$part->id)->assertOk()->assertSee('Dalies aprašymas');
        $part->update(['is_active'=>false]);
        $this->get('/en/listing/'.$part->id)->assertNotFound();
    }
    public function test_basket_submission_saves_snapshots_without_reducing_stock(): void
    {
        $part=$this->part();
        $this->post('/en/basket/'.$part->id,['quantity'=>2])->assertSessionHas('basket.'.$part->id,2);
        $this->get('/en/basket')->assertOk()->assertSee('51.00');
        $this->post('/en/order-request',$this->customer())->assertRedirect('/en/thank-you')->assertSessionMissing('basket');
        $order=OrderRequest::firstOrFail();
        $this->assertEquals('51.00',$order->subtotal);
        $this->assertEquals('Brake disc',$order->items->first()->title);
        $this->assertEquals(4,$part->fresh()->stock);
        $this->assertNull($order->delivery_quote);
    }
    public function test_stock_and_city_are_checked_server_side(): void
    {
        $part=$this->part();
        $this->post('/en/basket/'.$part->id,['quantity'=>5])->assertSessionHasErrors('basket');
        $this->withSession(['basket'=>[$part->id=>2]])->post('/en/order-request',array_replace($this->customer(),['city'=>'Kaunas']))->assertSessionHasErrors('city');
        $part->update(['stock'=>0]);
        $this->withSession(['basket'=>[$part->id=>2]])->post('/en/order-request',$this->customer())->assertSessionHasErrors('basket');
        $this->assertDatabaseCount('order_requests',0);
    }
    public function test_removal_preserves_other_items_and_handles_the_last_item(): void
    {
        $first=$this->part();
        $second=$this->part(['part_number'=>'SECOND']);
        $this->withSession(['basket'=>[$first->id=>2,$second->id=>1]])
            ->get('/en/basket')->assertOk()->assertSee('name="remove"',false)->assertSee('Remove');
        $this->from('/en/basket')->put('/en/basket',['remove'=>(string)$first->id,'quantities'=>[$second->id=>'invalid']])
            ->assertRedirect('/en/basket')->assertSessionHasNoErrors()
            ->assertSessionHas('basket',[$second->id=>1]);
        $this->from('/en/basket')->put('/en/basket',['remove'=>(string)$second->id])
            ->assertRedirect('/en/basket')->assertSessionHas('basket',[]);
        $this->get('/en/basket')->assertSee('Your basket is empty.')->assertDontSee('Submit order request');
        $this->assertEquals(4,$first->fresh()->stock);
    }
    public function test_zero_quantity_from_a_browser_removes_the_item(): void
    {
        $part=$this->part();
        $this->withSession(['basket'=>[$part->id=>2]])->put('/en/basket',['quantities'=>[$part->id=>'0']])
            ->assertSessionHasNoErrors()->assertSessionHas('basket',[]);
    }
    public function test_cars_cannot_be_added_to_parts_basket(): void
    {
        $car=$this->part(['type'=>'car']);
        $this->post('/en/basket/'.$car->id,['quantity'=>1])->assertNotFound();
    }
    public function test_admin_is_protected_and_stock_confirmation_is_idempotent(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
        $customer=User::factory()->create();
        $this->actingAs($customer)->get('/admin')->assertForbidden();
        $part=$this->part();
        $this->withSession(['basket'=>[$part->id=>2]])->post('/en/order-request',$this->customer());
        $order=OrderRequest::firstOrFail();
        $customer->forceFill(['is_admin'=>true])->save();
        $this->actingAs($customer)->get('/admin')->assertOk();
        $this->get('/admin/listings/'.$part->id.'/edit')->assertOk();
        $this->get('/admin/orders/'.$order->id)->assertOk();
        $url='/admin/orders/'.$order->id;
        $this->put($url,['status'=>'confirmed','delivery_quote'=>5])->assertSessionHasNoErrors();
        $this->assertEquals(2,$part->fresh()->stock);
        $this->put($url,['status'=>'completed','delivery_quote'=>5])->assertSessionHasNoErrors();
        $this->assertEquals(2,$part->fresh()->stock);
        $this->put($url,['status'=>'cancelled'])->assertSessionHasNoErrors();
        $this->assertEquals(4,$part->fresh()->stock);
        $this->put($url,['status'=>'cancelled'])->assertSessionHasNoErrors();
        $this->assertEquals(4,$part->fresh()->stock);
    }
    public function test_insufficient_stock_rolls_back_order_confirmation(): void
    {
        $part=$this->part();
        $this->withSession(['basket'=>[$part->id=>3]])->post('/en/order-request',$this->customer());
        $order=OrderRequest::firstOrFail();
        $part->update(['stock'=>1]);
        $admin=User::factory()->create(); $admin->forceFill(['is_admin'=>true])->save();
        $this->actingAs($admin)->put('/admin/orders/'.$order->id,['status'=>'confirmed'])->assertSessionHasErrors('stock');
        $this->assertEquals('new',$order->fresh()->status);
        $this->assertEquals(1,$part->fresh()->stock);
    }
}
