<?php

namespace App\Http\Controllers;

use App\Models\Bale;
use App\Models\SectionLabour;
use App\Http\Requests\StoreSectionLabourRequest;
use App\Http\Requests\UpdateSectionLabourRequest;
use App\Models\Subcategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SectionLabourController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = SectionLabour::query();
        $query->with('subcategory');
        $query->when($request->has('subcategory_id') && $request->input('subcategory_id') != '', function ($q) use ($request) {
            $q->where('subcategory_id', $request->input('subcategory_id'));
        });
        $query->when($request->has('date'), function ($q) use ($request) {
            $q->whereDate('date', $request->input('date'));
        });
        
        $query->when($request->has('from_date') && $request->has('to_date'), function ($q) use ($request) {
            $q->whereBetween('date', [$request->input('from_date'), $request->input('to_date')]);
        });

        $query->orderBy('date', 'desc');
        $query->orderBy('created_at', 'desc');
        $sectionLabours = $query->paginate(10);

        $subcategories = Subcategory::all();

        return view('section_labours.index', compact('sectionLabours', 'subcategories'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSectionLabourRequest $request)
    {
        $sectionLabour = SectionLabour::create($request->validated());

        return redirect()->route('section-labours.index')->with('success', 'Section Labour created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(SectionLabour $sectionLabour)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(SectionLabour $sectionLabour)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSectionLabourRequest $request, SectionLabour $sectionLabour)
    {
        $sectionLabour->update($request->validated());

        return redirect()->route('section-labours.index')->with('success', 'Section Labour updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SectionLabour $sectionLabour)
    {
        $sectionLabour->delete();

        return redirect()->route('section-labours.index')->with('success', 'Section Labour deleted successfully.');
    }

    public function sectionProductionReport(Request $request)
    {
        $date = $request->get('date', now()->format('Y-m-d'));

        // Get all sections
        $sections = Subcategory::orderBy('name')->get();

        // Get production qty grouped by section for the given date
        $productionBySection = Bale::select('products.subcategory_id', DB::raw('COUNT(bales.id) as total_qty'))
            ->join('packinglists', 'bales.packinglist_id', '=', 'packinglists.id')
            ->join('products', 'packinglists.product_id', '=', 'products.id')
            ->whereDate('bales.created_at', $date)
            ->where('bales.type', 'production')
            ->groupBy('products.subcategory_id')
            ->pluck('total_qty', 'products.subcategory_id');

        // Get labour count for each section for the given date
        $labourBySection = SectionLabour::where('date', $date)
            ->pluck('labour_count', 'subcategory_id');

        return view('section_labours.section_production_report', [
            'date' => $date,
            'sections' => $sections,
            'productionBySection' => $productionBySection,
            'labourBySection' => $labourBySection,
        ]);
    }
}
