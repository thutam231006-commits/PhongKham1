<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ServiceController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->input('q', ''));
        $category = trim((string) $request->input('category', ''));
        $sort = trim((string) $request->input('sort', 'default'));
        $query = Service::query()->where('status', 'ACTIVE')->whereHas('category', fn ($categoryQuery) => $categoryQuery->where('is_active', true))->with('category');

        if ($search !== '') {
            $query->where(fn ($builder) => $builder->where('name', 'like', "%{$search}%")->orWhere('description', 'like', "%{$search}%"));
        }
        if ($category !== '') {
            $query->whereHas('category', fn ($categoryQuery) => $categoryQuery->whereKey($category)->orWhere('name', $category));
        }
        match ($sort) {
            'price_asc' => $query->orderBy('price'),
            'price_desc' => $query->orderByDesc('price'),
            'duration_desc' => $query->orderByDesc('duration_minutes'),
            'name_asc' => $query->orderBy('name'),
            default => $query->orderBy('display_order'),
        };

        $services = $query->paginate(20)->withQueryString();
        $categories = ServiceCategory::query()->where('is_active', true)->orderBy('display_order')->get();

        return view('public.services', compact('services', 'categories', 'search', 'category', 'sort'));
    }

    public function show(Service $service): View
    {
        abort_unless($service->status === 'ACTIVE' && $service->category?->is_active, 404);
        $service->load(['category', 'specialists' => fn ($query) => $query->where('status', 'ACTIVE')->with('user')]);
        $relatedServices = Service::query()->where('status', 'ACTIVE')->where('category_id', $service->category_id)->whereKeyNot($service->id)->orderBy('display_order')->limit(3)->get();

        return view('public.service-detail', ['service' => $service, 'specialists' => $service->specialists, 'relatedServices' => $relatedServices]);
    }
}
