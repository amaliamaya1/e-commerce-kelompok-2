<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Create Users
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'adminkore@gmail.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);
        
        \App\Models\UserBalance::create(['user_id' => $admin->id, 'balance' => 0]);
        // Admin doesn't strictly need a buyer profile, but good for consistency if they buy stuff? 
        // ERD: Buyer has user_id.

        $member1 = User::factory()->create([
            'name' => 'Member One',
            'email' => 'member1@example.com',
            'password' => bcrypt('password'),
            'role' => 'member', 
        ]);
        \App\Models\UserBalance::create(['user_id' => $member1->id, 'balance' => 1000000]); // Initial balance
        \App\Models\Buyer::create(['user_id' => $member1->id, 'phone_number' => '08123456789']);

        $member2 = User::factory()->create([
            'name' => 'Member Two',
            'email' => 'member2@example.com',
            'password' => bcrypt('password'),
            'role' => 'member',
        ]);
        \App\Models\UserBalance::create(['user_id' => $member2->id, 'balance' => 0]);
        \App\Models\Buyer::create(['user_id' => $member2->id]);

        // 2. Create Store (owned by member1 - so member1 becomes seller)
        // Update member1 role to seller
        $member1->role = 'seller';
        $member1->save();

        $store = \App\Models\Store::create([
            'user_id' => $member1->id,
            'slug' => 'k-store-official',
            'name' => 'K-Store Official',
            'about' => 'The best Korean stuff.',
            'is_verified' => true,
        ]);
        
        // Create StoreBalance
        \App\Models\StoreBalance::create(['store_id' => $store->id, 'balance' => 0]);

        // 3. Create Categories & Products with Specific Images
        $categoriesMap = [
            'Album' => ['album1.jpg', 'album2.jpg', 'album3.jpg', 'album4.jpg', 'album5.jpg', 'album6.jpg', 'album7.jpg'],
            'Lightstick' => ['lightstick1.jpg', 'lightstick2.jpg', 'lightstick3.jpg', 'lightstick4.jpg', 'lightstick5.jpg', 'lightstick6.jpg', 'lightstick7.jpg', 'lightstick8.jpg', 'lightstick9.jpg'],
            'Photocard' => ['photocard1.jpg', 'photocard2.jpg', 'photocard3.jpg', 'photocard4.jpg', 'photocard5.jpg'],
            'Apparel' => ['hoodie1.jpg', 'hoodie2.jpg', 'jersey1.jpg', 'jersey2.jpg', 'kaos1.jpg', 'kaos2.jpg', 'kaos3.jpg'],
            'Doll' => ['doll1.jpg', 'doll2.jpg', 'doll3.jpg', 'doll4.jpg'],
            'Keychains & Stickers' => ['keychain1.jpg', 'keychain2.jpg', 'keychain3.jpg', 'sticker1.jpg', 'sticker2.jpg', 'sticker3.jpg'],
        ];

        // Ensure storage directory exists
        if(!\Illuminate\Support\Facades\Storage::disk('public')->exists('products')) {
            \Illuminate\Support\Facades\Storage::disk('public')->makeDirectory('products');
        }

        foreach ($categoriesMap as $catName => $images) {
            $category = \App\Models\ProductCategory::create([
                'name' => $catName, 
                'slug' => \Illuminate\Support\Str::slug($catName),
                'image' => 'default-icon.png',
                'description' => 'Best ' . $catName
            ]);

            foreach ($images as $index => $imageFile) {
                // Copy file from public to storage/app/public/products
                $sourcePath = public_path($imageFile); // Assuming images are in public root
                $targetPath = 'products/' . $imageFile;
                
                // For seeding demo, if file doesn't exist, we skip copying but still create product
                if (file_exists($sourcePath)) {
                    \Illuminate\Support\Facades\Storage::disk('public')->put($targetPath, file_get_contents($sourcePath));
                }

                $product = \App\Models\Product::create([
                    'store_id' => $store->id,
                    'product_category_id' => $category->id,
                    'name' => $catName . ' ' . ($index + 1), // e.g. Album 1
                    'slug' => \Illuminate\Support\Str::slug($catName . '-' . ($index + 1) . '-' . uniqid()),
                    'about' => 'Official ' . $catName . ' - High quality K-Pop merchandise.',
                    'condition' => 'new',
                    'price' => rand(50000, 750000),
                    'weight' => rand(100, 1000),
                    'stock' => rand(10, 50),
                ]);

                \App\Models\ProductImage::create([
                    'product_id' => $product->id,
                    'image' => $targetPath,
                    'is_thumbnail' => true,
                ]);
            }
        }
    }
}
