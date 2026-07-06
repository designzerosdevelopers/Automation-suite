<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Repositories\LeadRepository;
use App\Repositories\CallLogRepository;
use App\Repositories\BookingRepository;
use Illuminate\Http\Request;

class LeadController extends Controller
{
    protected LeadRepository $leadRepository;
    protected CallLogRepository $callLogRepository;
    protected BookingRepository $bookingRepository;

    public function __construct(
        LeadRepository $leadRepository,
        CallLogRepository $callLogRepository,
        BookingRepository $bookingRepository
    ) {
        $this->leadRepository = $leadRepository;
        $this->callLogRepository = $callLogRepository;
        $this->bookingRepository = $bookingRepository;
    }

    public function index(Request $request)
    {
        $query = Lead::query();

        // Filters
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('phone', 'LIKE', "%{$search}%")
                    ->orWhere('email', 'LIKE', "%{$search}%");
            });
        }

        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        if ($request->has('source') && $request->source) {
            $query->where('source', $request->source);
        }

        $leads = $query->latest()->paginate(50);

        // Status counts
        $statusCounts = Lead::select('status', \DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        return view('admin.leads', compact('leads', 'statusCounts'));
    }

    public function show(Lead $lead)
    {
        $calls = $this->callLogRepository->getForLead($lead->id);
        $bookings = $this->bookingRepository->getForLead($lead->id);

        return view('admin.leads-show', compact('lead', 'calls', 'bookings'));
    }

    public function update(Request $request, Lead $lead)
    {
        $request->validate([
            'name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'status' => 'nullable|string|in:new,contacted,booked,converted,lost',
            'notes' => 'nullable|string',
        ]);

        $this->leadRepository->update($lead, $request->only([
            'name', 'email', 'phone', 'status', 'notes'
        ]));

        return back()->with('success', 'Lead updated successfully!');
    }

    public function addNote(Request $request, Lead $lead)
    {
        $request->validate([
            'note' => 'required|string',
        ]);

        $notes = $lead->notes ? $lead->notes . "\n\n" . now()->toDateTimeString() . ": " . $request->note : $request->note;

        $this->leadRepository->update($lead, ['notes' => $notes]);

        return back()->with('success', 'Note added successfully!');
    }

    public function destroy(Lead $lead)
    {
        $this->leadRepository->delete($lead);

        return redirect()->route('admin.leads.index')
            ->with('success', 'Lead deleted successfully!');
    }

    public function export(Request $request)
    {
        $query = Lead::query();

        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        $leads = $query->get();

        $filename = 'leads-' . now()->format('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename={$filename}",
        ];

        $callback = function () use ($leads) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['ID', 'Name', 'Phone', 'Email', 'Source', 'Status', 'Total Calls', 'Created At']);

            foreach ($leads as $lead) {
                fputcsv($file, [
                    $lead->id,
                    $lead->name,
                    $lead->phone,
                    $lead->email,
                    $lead->source,
                    $lead->status,
                    $lead->total_calls,
                    $lead->created_at,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}