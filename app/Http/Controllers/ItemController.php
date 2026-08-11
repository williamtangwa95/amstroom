<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ItemController extends Controller
{
    public function index()
    {
        $items = Item::with('category')->where('is_admin_item', false)->latest()->get();
        return view('items.index', compact('items'));
    }

    public function create()
    {
        $categories = Category::where('is_admin_category', false)->orderBy('category_name')->get();
        return view('items.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'item_name'      => 'required|string|max:150',
            'category_id'    => 'required|exists:categories,id',
            'specification'  => 'nullable|string',
            'brand'          => 'nullable|string|max:100',
            'model'          => 'nullable|string|max:100',
            'warranty_period'=> 'nullable|string|max:50',
            'image'          => 'nullable|image|max:1024',
        ]);

        $data = $request->only(
            'item_name', 'category_id', 'specification', 'brand', 'model', 'warranty_period'
        );

        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('items', 'public');
        }

        Item::create($data);

        return redirect()->route('items.index')
            ->with('success', 'Item registered successfully.');
    }

    public function show(Item $item)
    {
        $item->load('category', 'mainStocks', 'shopStocks.shop', 'components.childItem');
        
        $currentComponentsIds = $item->components->pluck('component_item_id')->toArray();
        $allItems = Item::where('is_admin_item', false)
            ->where('id', '!=', $item->id)
            ->whereNotIn('id', $currentComponentsIds)
            ->orderBy('item_name')
            ->get();

        return view('items.show', compact('item', 'allItems'));
    }

    public function edit(Item $item)
    {
        $categories = Category::where('is_admin_category', false)->orderBy('category_name')->get();
        return view('items.edit', compact('item', 'categories'));
    }

    public function update(Request $request, Item $item)
    {
        $request->validate([
            'item_name'      => 'required|string|max:150',
            'category_id'    => 'required|exists:categories,id',
            'specification'  => 'nullable|string',
            'brand'          => 'nullable|string|max:100',
            'model'          => 'nullable|string|max:100',
            'warranty_period'=> 'nullable|string|max:50',
            'image'          => 'nullable|image|max:1024',
        ]);

        $data = $request->only(
            'item_name', 'category_id', 'specification', 'brand', 'model', 'warranty_period'
        );

        if ($request->hasFile('image')) {
            if ($item->image_path) {
                Storage::disk('public')->delete($item->image_path);
            }
            $data['image_path'] = $request->file('image')->store('items', 'public');
        }

        $item->update($data);

        return redirect()->route('items.index')
            ->with('success', 'Item updated successfully.');
    }

    public function destroy(Item $item)
    {
        if ($item->image_path) {
            Storage::disk('public')->delete($item->image_path);
        }
        $item->delete();
        return redirect()->route('items.index')
            ->with('success', 'Item deleted successfully.');
    }

    public function uploadImage(Request $request, Item $item)
    {
        $request->validate([
            'image' => 'required|image|max:1024', // max 1MB (1024KB)
        ]);

        if ($request->hasFile('image')) {
            if ($item->image_path) {
                Storage::disk('public')->delete($item->image_path);
            }
            $path = $request->file('image')->store('items', 'public');
            $item->update(['image_path' => $path]);

            return back()->with('success', 'Product image uploaded successfully.');
        }

        return back()->with('error', 'Failed to upload image.');
    }

    public function addComponent(Request $request, Item $item)
    {
        $request->validate([
            'component_item_id' => 'required|exists:items,id',
            'quantity' => 'required|integer|min:1',
        ]);

        $componentId = $request->component_item_id;
        if ($componentId == $item->id) {
            return back()->with('error', 'An item cannot be a component of itself.');
        }

        $exists = \App\Models\ItemComponent::where('parent_item_id', $item->id)
            ->where('component_item_id', $componentId)
            ->exists();

        if ($exists) {
            return back()->with('error', 'This item is already a component.');
        }

        \App\Models\ItemComponent::create([
            'parent_item_id' => $item->id,
            'component_item_id' => $componentId,
            'quantity' => $request->quantity,
        ]);

        return back()->with('success', 'Component added successfully.');
    }

    public function removeComponent(Item $item, \App\Models\ItemComponent $component)
    {
        if ($component->parent_item_id !== $item->id) {
            abort(403, 'Unauthorized component deletion.');
        }

        $component->delete();

        return back()->with('success', 'Component removed successfully.');
    }
}
