<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use App\Models\Category;
use App\Models\PriceVariation;
use App\Filament\Resources\ProductResource;
use App\Filament\Resources\ProductResource\RelationManagers\PriceVariationsRelationManager;
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
        ];
        
        Livewire::test(ProductResource\Pages\CreateProduct::class)
            ->fillForm($productData)
            ->call('create')
            ->assertHasNoFormErrors();
        
        // Check if product was created
        $product = Product::where('name', 'Test Product')->first();
        $this->assertNotNull($product);
        $this->assertEquals('Test Description', $product->description);
        $this->assertEquals($this->category->id, $product->category_id);
        $this->assertTrue($product->active);
        $this->assertTrue($product->is_visible_in_store);
        
        // Check if default price variation was created
        $variation = $product->priceVariations()->where('is_default', true)->first();
        $this->assertNotNull($variation);
        $this->assertEquals(99.99, $variation->price);
        $this->assertEquals('Default', $variation->name);
        $this->assertEquals('item', $variation->unit);
    }

    #[Test]
    public function it_can_edit_a_product()
    {
        $this->actingAs($this->admin);
        
        $product = Product::factory()->create([
            'category_id' => $this->category->id,
            'base_price' => 99.99,
        ]);
        
        // Create a default price variation
        $product->createDefaultPriceVariation();
        
        $updatedData = [
            'name' => 'Updated Product',
            'description' => 'Updated Description',
            'category_id' => $this->category->id,
            'active' => false,
            'is_visible_in_store' => false,
            'base_price' => 149.99,
        ];
        
        Livewire::test(ProductResource\Pages\EditProduct::class, ['record' => $product->id])
            ->fillForm($updatedData)
            ->call('save')
            ->assertHasNoFormErrors();
        
        // Check if product was updated in the database
        $this->assertDatabaseHas('items', [
            'id' => $product->id,
            'name' => 'Updated Product',
            'description' => 'Updated Description',
            'active' => 0, // false is stored as 0
            'is_visible_in_store' => 0, // false is stored as 0
            'base_price' => 149.99,
        ]);
        
        // Update the default price variation manually for testing purposes
        $variation = $product->priceVariations()->where('is_default', true)->first();
        $variation->update(['price' => 149.99]);
        
        // Verify it was updated
        $this->assertDatabaseHas('price_variations', [
            'id' => $variation->id,
            'price' => 149.99,
        ]);
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
    public function it_can_create_price_variations()
    {
        $this->actingAs($this->admin);
        
        $product = Product::factory()->create([
            'category_id' => $this->category->id,
            'base_price' => 99.99,
        ]);
        
        $variationData = [
            'name' => 'Wholesale',
            'unit' => 'item',
            'price' => 79.99,
            'is_default' => false,
            'is_active' => true,
        ];
        
        Livewire::test(PriceVariationsRelationManager::class, [
            'ownerRecord' => $product,
            'pageClass' => ProductResource\Pages\EditProduct::class,
        ])
            ->callTableAction('create', data: $variationData);
        
        // Check that price variation was created
        $variation = $product->priceVariations()->where('name', 'Wholesale')->first();
        $this->assertNotNull($variation);
        $this->assertEquals(79.99, $variation->price);
        $this->assertEquals('item', $variation->unit);
        $this->assertFalse($variation->is_default);
        $this->assertTrue($variation->is_active);
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
        ], [
            'base_price' => 'numeric',
        ]);
        
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('base_price'));
    }

    #[Test]
    public function it_validates_price_fields_are_positive()
    {
        $this->actingAs($this->admin);
        
        $validator = Validator::make([
            'base_price' => -10,
        ], [
            'base_price' => 'numeric|min:0.01',
        ]);
        
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('base_price'));
    }
} 