<?php

namespace App\Http\Controllers;

use App\Models\ExpenseCategory;
use Illuminate\Http\Request;

class ExpenseCategoryController extends Controller
{
    public function index()
    {
        $categories = ExpenseCategory::with('creator')->latest()->get();
        return view('expense-categories.index', compact('categories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100|unique:expense_categories,name',
        ]);

        ExpenseCategory::create([
            'name' => $request->name,
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('expense-categories.index')
            ->with('success', 'Expense category added successfully.');
    }

    public function destroy(ExpenseCategory $category)
    {
        $category->delete();
        return redirect()->route('expense-categories.index')
            ->with('success', 'Expense category deleted successfully.');
    }

    public function update(Request $request, $id)
    {
        $category = ExpenseCategory::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:100|unique:expense_categories,name,' . $id,
        ]);

        $category->update([
            'name' => $request->name,
        ]);

        return redirect()->route('expense-categories.index')
            ->with('success', 'Expense category updated successfully.');
    }
}
