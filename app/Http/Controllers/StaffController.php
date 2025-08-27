<?php

namespace App\Http\Controllers;

use App\Models\Staff;
use Illuminate\Http\Request;
use Inertia\Inertia;

class StaffController extends Controller
{
    // Display staff management page
    public function index()
    {
        $staff = Staff::all();
        return Inertia::render('Restaurant/Staff', [
            'staff' => $staff
        ]);
    }

    // Add new staff
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:staff,email',
            'role' => 'required|string',
            'phone' => 'nullable|string',
            'address' => 'nullable|string',
            'salary' => 'nullable|numeric',
            'start_date' => 'nullable|date',
            'emergency_contact' => 'nullable|string',
            'notes' => 'nullable|string',
            'active_today' => 'boolean',
        ]);

        $staff = Staff::create(array_merge($validated, [
            'start_date' => $validated['start_date'] ?? now()->toDateString(),
            'active_today' => $validated['active_today'] ?? false,
        ]));

        return redirect()->route('staff.index')->with('success', 'Staff member added successfully.');
    }

    // Update staff
    public function update(Request $request, $id)
    {
        $staff = Staff::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:staff,email,' . $id,
            'role' => 'required|string',
            'phone' => 'nullable|string',
            'address' => 'nullable|string',
            'salary' => 'nullable|numeric',
            'start_date' => 'nullable|date',
            'emergency_contact' => 'nullable|string',
            'notes' => 'nullable|string',
            'active_today' => 'boolean',
        ]);

        $staff->update($validated);

        return redirect()->route('staff.index')->with('success', 'Staff member updated successfully.');
    }

    // Delete staff
    public function destroy($id)
    {
        $staff = Staff::findOrFail($id);
        $staff->delete();

        return redirect()->route('staff.index')->with('success', 'Staff member deleted successfully.');
    }
}
