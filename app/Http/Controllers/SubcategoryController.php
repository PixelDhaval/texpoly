<?php

namespace App\Http\Controllers;

use App\Models\Subcategory;
use Illuminate\Http\Request;

class SubcategoryController extends Controller
{
    public function index()
    {
        $subcategories = Subcategory::all();
        return view('subcategories.index', compact('subcategories'));
    }

    public function store(Request $request)
    {
        $request->validate(['name' => 'required|string|max:255']);
        Subcategory::create($request->all());
        return redirect()->route('subcategories.index')->with('success', 'Section created successfully');
    }

    public function edit(Subcategory $subcategory)
    {
        $subcategories = Subcategory::all();
        return view('subcategories.index', [
            'subcategories' => $subcategories,
            'editSubcategory' => $subcategory
        ]);
    }

    public function update(Request $request, Subcategory $subcategory)
    {
        $request->validate(['name' => 'required|string|max:255']);
        $subcategory->update($request->all());
        return redirect()->route('subcategories.index')->with('success', 'Section updated successfully');
    }

    public function destroy(Subcategory $subcategory)
    {
        $subcategory->delete();
        return redirect()->route('subcategories.index')->with('success', 'Section deleted successfully');
    }
}
