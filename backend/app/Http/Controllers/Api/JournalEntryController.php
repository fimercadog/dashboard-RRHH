<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\JournalEntryResource;
use App\Models\AccountingPeriod;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Services\AccountingService;
use App\Services\AuditService;
use Illuminate\Http\Request;

class JournalEntryController extends BaseCrudController
{
    protected string $model    = JournalEntry::class;
    protected string $resource = JournalEntryResource::class;
    protected array  $with     = ['lines.account'];
    protected array  $filterable = ['status', 'reference_type', 'accounting_period_id'];

    public function __construct(private AccountingService $accounting) {}

    public function show(Request $request, string $id)
    {
        $entry = JournalEntry::where('company_id', $this->companyId($request))
            ->with(['lines.account'])
            ->findOrFail($id);

        return new JournalEntryResource($entry);
    }

    /** POST /journal-entries — asiento manual. */
    public function store(Request $request, AuditService $audit)
    {
        $data = $request->validate([
            'accounting_period_id' => ['required', 'integer'],
            'date'                 => ['required', 'date'],
            'description'          => ['required', 'string', 'max:200'],
            'lines'                => ['required', 'array', 'min:2'],
            'lines.*.account_id'   => ['required', 'integer'],
            'lines.*.description'  => ['nullable', 'string', 'max:200'],
            'lines.*.debit'        => ['required', 'numeric', 'min:0'],
            'lines.*.credit'       => ['required', 'numeric', 'min:0'],
        ]);

        $companyId = $this->companyId($request);

        $period = AccountingPeriod::where('company_id', $companyId)
            ->where('status', 'open')
            ->findOrFail($data['accounting_period_id']);

        $entry = JournalEntry::create([
            'company_id'           => $companyId,
            'accounting_period_id' => $period->id,
            'number'               => 'MAN-'.now()->format('Ymd').'-'.rand(1000, 9999),
            'date'                 => $data['date'],
            'description'          => $data['description'],
            'status'               => 'draft',
            'reference_type'       => 'manual',
            'reference_id'         => null,
            'user_id'              => $request->user()->id,
        ]);

        foreach ($data['lines'] as $seq => $line) {
            JournalEntryLine::create([
                'journal_entry_id' => $entry->id,
                'account_id'       => $line['account_id'],
                'description'      => $line['description'] ?? null,
                'debit'            => $line['debit'],
                'credit'           => $line['credit'],
                'sequence'         => $seq + 1,
            ]);
        }

        $this->accounting->postEntry($entry, $request->user()->id);

        $entry->load('lines.account');
        $audit->record('created', $entry, $request);

        return (new JournalEntryResource($entry))->response()->setStatusCode(201);
    }

    /** POST /journal-entries/{id}/reverse */
    public function reverse(Request $request, string $id, AuditService $audit): JournalEntryResource
    {
        $entry = JournalEntry::where('company_id', $this->companyId($request))->findOrFail($id);

        $reversal = $this->accounting->reverseEntry($entry, $request->user()->id);
        $reversal->load('lines.account');

        $audit->record('journal_entry_reversed', $reversal, $request);

        return new JournalEntryResource($reversal);
    }

    /** POST /journal-entries/{id}/post — post manual de un draft. */
    public function post(Request $request, string $id, AuditService $audit): JournalEntryResource
    {
        $entry = JournalEntry::where('company_id', $this->companyId($request))->findOrFail($id);

        $this->accounting->postEntry($entry, $request->user()->id);
        $entry->load('lines.account');

        $audit->record('journal_entry_posted', $entry, $request);

        return new JournalEntryResource($entry);
    }

    /** POST /accounting-periods/{id}/opening — asiento de apertura manual. */
    public function opening(Request $request, string $periodId, AuditService $audit): JournalEntryResource
    {
        $data = $request->validate([
            'balances'            => ['required', 'array'],
            'balances.*.account_id' => ['required', 'integer'],
            'balances.*.debit'    => ['required', 'numeric', 'min:0'],
            'balances.*.credit'   => ['required', 'numeric', 'min:0'],
        ]);

        $period = AccountingPeriod::where('company_id', $this->companyId($request))
            ->where('status', 'open')
            ->findOrFail($periodId);

        $accountBalances = collect($data['balances'])
            ->keyBy('account_id')
            ->map(fn ($b) => ['debit' => $b['debit'], 'credit' => $b['credit']])
            ->all();

        $entry = $this->accounting->createOpeningEntry($period, $accountBalances, $request->user()->id);
        $entry->load('lines.account');

        $audit->record('journal_entry_opening', $entry, $request);

        return (new JournalEntryResource($entry));
    }
}
