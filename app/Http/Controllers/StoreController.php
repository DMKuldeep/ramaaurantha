<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class StoreController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::query();
        $search = trim((string) $request->query('q', ''));
        $category = $request->query('category');
        $tag = $request->query('tag');

        if ($search !== '') {
            $query->where(function ($builder) use ($search): void {
                $builder->where('name', 'like', '%'.$search.'%')
                    ->orWhere('code', 'like', '%'.$search.'%')
                    ->orWhere('tags', 'like', '%'.$search.'%');
            });
        }
        if (in_array($category, ['urli', 'soy'], true)) {
            $query->where('category', $category);
        }
        if (is_string($tag) && $tag !== '') {
            $query->whereJsonContains('tags', $tag);
        }

        match ($request->query('sort')) {
            'price-low' => $query->orderBy('price'),
            'price-high' => $query->orderByDesc('price'),
            'name' => $query->orderBy('name'),
            default => $query->orderBy('created_at')->orderBy('code'),
        };

        return view('store.index', [
            'products' => $query->paginate(24)->withQueryString(),
            'search' => $search,
            'category' => $category,
            'tag' => $tag,
            'sort' => $request->query('sort', 'featured'),
        ]);
    }

    public function show(Product $product)
    {
        return view('store.show', compact('product'));
    }
}
