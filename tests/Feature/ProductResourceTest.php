<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use App\Models\Category;
use App\Filament\Resources\ProductResource;
use Illuminate\Support\Facades\Notification;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Filament\Pages\Actions\CreateAction;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Support\Facades\Validator;

class ProductResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Disable notifications
        Notification::fake();
        
        // Run the role seeder
        $this->seed(RoleSeeder::class);
        
        // Create an admin user
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
        
        // Create a test category
        $this->category = Category::factory()->create();
    }

    #[Test]
    public function it_can_list_products()
    {
        $this->actingAs($this->admin);
        
        $response = $this->get('/admin/products');
        
        $response->assertSuccessful();
    }

    #[Test]
    public function it_can_create_a_product()
    {
        $this->actingAs($this->admin);
        
        $productData = [
            'name' => 'Test Product',
            'description' => 'Test Description',
            'category_id' => $this->category->id,
            'active' => true,
            'is_visible_in_store' => true,
            'base_price' => 99.99,
            'wholesale_price' => 79.99,
            'bulk_price' => 69.99,
            'special_price' => 89.99,
        ];
        
        Livewire::test(ProductResource\Pages\CreateProduct::class)
            ->fillForm($productData)
            ->call('create')
            ->assertHasNoFormErrors();
        
        $this->assertDatabaseHas('items', $productData);
    }

    #[Test]
    public function it_can_edit_a_product()
    {
        $this->actingAs($this->admin);
        
        $product = Product::factory()->create([
            'category_id' => $this->category->id,
        ]);
        
        $updatedData = [
            'name' => 'Updated Product',
            'description' => 'Updated Description',
            'category_id' => $this->category->id,
            'active' => false,
            'is_visible_in_store' => false,
            'base_price' => 149.99,
            'wholesale_price' => 129.99,
            'bulk_price' => 119.99,
            'special_price' => 139.99,
        ];
        
        Livewire::test(ProductResource\Pages\EditProduct::class, ['record' => $product->id])
            ->fillForm($updatedData)
            ->call('save')
            ->assertHasNoFormErrors();
        
        $this->assertDatabaseHas('items', array_merge(
            ['id' => $product->id],
            $updatedData
        ));
    }

    #[Test]
    public function it_can_delete_a_product()
    {
        $this->actingAs($this->admin);
        
        $product = Product::factory()->create([
            'category_id' => $this->category->id,
        ]);
        
        Livewire::test(ProductResource\Pages\EditProduct::class, ['record' => $product->id])
            ->callAction('delete');
            
        $this->assertSoftDeleted('items', [
            'id' => $product->id,
        ]);
    }

    #[Test]
    public function it_validates_required_fields_when_creating()
    {
        $this->actingAs($this->admin);
        
        $validator = Validator::make([
            'name' => '',
            'category_id' => null,
            'base_price' => null,
        ], [
            'name' => 'required',
            'category_id' => 'required',
            'base_price' => 'required',
        ]);
        
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('name'));
        $this->assertTrue($validator->errors()->has('category_id'));
        $this->assertTrue($validator->errors()->has('base_price'));
    }

    #[Test]
    public function it_validates_price_fields_are_numeric()
    {
        $this->actingAs($this->admin);
        
        $validator = Validator::make([
            'base_price' => 'not-a-number',
            'wholesale_price' => 'not-a-number',
            'bulk_price' => 'not-a-number',
            'special_price' => 'not-a-number',
        ], [
            'base_price' => 'numeric',
            'wholesale_price' => 'numeric',
            'bulk_price' => 'numeric',
            'special_price' => 'numeric',
        ]);
        
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('base_price'));
        $this->assertTrue($validator->errors()->has('wholesale_price'));
        $this->assertTrue($validator->errors()->has('bulk_price'));
        $this->assertTrue($validator->errors()->has('special_price'));
    }

    #[Test]
    public function it_validates_price_fields_are_positive()
    {
        $this->actingAs($this->admin);
        
        $validator = Validator::make([
            'base_price' => -10,
            'wholesale_price' => -5,
            'bulk_price' => -3,
            'special_price' => -7,
        ], [
            'base_price' => 'numeric|min:0.01',
            'wholesale_price' => 'numeric|min:0.01',
            'bulk_price' => 'numeric|min:0.01',
            'special_price' => 'numeric|min:0.01',
        ]);
        
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('base_price'));
        $this->assertTrue($validator->errors()->has('wholesale_price'));
        $this->assertTrue($validator->errors()->has('bulk_price'));
        $this->assertTrue($validator->errors()->has('special_price'));
    }
} 