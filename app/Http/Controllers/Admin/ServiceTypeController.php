<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Office;
use App\Models\ServiceType;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ServiceTypeController extends Controller
{
    public function index()
    {
        $serviceTypes = ServiceType::with(['office', 'subOffice'])->latest()->get();
        $offices = Office::with('subOffices')->get();

        return view('admin.service-types.index', compact('serviceTypes', 'offices'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'office_id' => 'required|exists:offices,id',
            'sub_office_id' => [
                'nullable',
                Rule::exists('office_sub_offices', 'id')->where(fn($query) => $query->where('office_id', $request->office_id)),
            ],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('service_types')
                    ->where(fn($query) => $query
                        ->where('office_id', $request->office_id)
                        ->where('sub_office_id', $request->sub_office_id)),
            ],
        ]);

        $name = trim(ucwords(strtolower($request->name)));

        ServiceType::create([
            'office_id' => $request->office_id,
            'sub_office_id' => $request->sub_office_id,
            'name' => $name,
        ]);

        return back()->with('success', 'Service type created successfully.');
    }

    public function update(Request $request, ServiceType $serviceType)
    {
        $request->validate([
            'office_id' => 'required|exists:offices,id',
            'sub_office_id' => [
                'nullable',
                Rule::exists('office_sub_offices', 'id')->where(fn($query) => $query->where('office_id', $request->office_id)),
            ],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('service_types')
                    ->where(fn($query) => $query
                        ->where('office_id', $request->office_id)
                        ->where('sub_office_id', $request->sub_office_id))
                    ->ignore($serviceType->id),
            ],
        ]);

        $name = trim(ucwords(strtolower($request->name)));

        $serviceType->update([
            'office_id' => $request->office_id,
            'sub_office_id' => $request->sub_office_id,
            'name' => $name,
        ]);

        return back()->with('success', 'Service type updated successfully.');
    }

    public function destroy(ServiceType $serviceType)
    {
        $serviceType->delete();

        return back()->with('success', 'Service type deleted successfully.');
    }
}
