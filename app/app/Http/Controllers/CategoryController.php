<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $userId = $request->user()->id;
        $categories = Category::forUser($userId)
            ->withCount(['transactions' => function ($query) use ($userId) {
                $query->where('user_id', $userId);
            }])
            ->orderBy('type')
            ->orderBy('name')
            ->get();

        return Inertia::render('Categories/Index', [
            'categories' => $categories,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:50'],
            'type' => ['required', Rule::in(['expense', 'income'])],
            'icon' => ['required', 'string', 'max:10'],
            'color' => ['required', 'string', 'max:7', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ]);

        $request->user()->categories()->create($validated);

        return redirect()->route('categories.index')
            ->with('success', 'Kategoria została dodana.');
    }

    public function update(Request $request, Category $category)
    {
        if ($category->user_id !== $request->user()->id) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:50'],
            'type' => ['required', Rule::in(['expense', 'income'])],
            'icon' => ['required', 'string', 'max:10'],
            'color' => ['required', 'string', 'max:7', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ]);

        $category->update($validated);

        return redirect()->route('categories.index')
            ->with('success', 'Kategoria została zaktualizowana.');
    }

    public function destroy(Request $request, Category $category)
    {
        if ($category->user_id !== $request->user()->id) {
            abort(403);
        }

        if ($category->is_default) {
            return back()->withErrors(['category' => 'Nie można usunąć domyślnej kategorii.']);
        }

        $category->delete();

        return redirect()->route('categories.index')
            ->with('success', 'Kategoria została usunięta.');
    }
}
