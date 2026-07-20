<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CompanyFeedbackRequest;
use App\Models\Company;
use App\Models\CompanyFeedback;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CompanyFeedbackController extends Controller
{
    private function getCompanyId(): int
    {
        $user = Auth::user();
        return Company::where('user_id', $user->id)->value('id')
            ?? throw new \RuntimeException('Company profile not found');
    }

    public function index()
    {
        $companyId = $this->getCompanyId();
        $feedback = CompanyFeedback::where('company_id', $companyId)
            ->latest()
            ->get();

        return response()->json(['data' => $feedback]);
    }

    public function store(CompanyFeedbackRequest $request)
    {
        $companyId = $this->getCompanyId();

        $feedback = CompanyFeedback::create([
            'company_id' => $companyId,
            'title' => $request->title,
            'message' => $request->message,
        ]);

        return response()->json($feedback, 201);
    }

    public function show(string $id)
    {
        $companyId = $this->getCompanyId();
        $feedback = CompanyFeedback::where('company_id', $companyId)
            ->findOrFail($id);

        return response()->json($feedback);
    }

    public function update(CompanyFeedbackRequest $request, string $id)
    {
        $companyId = $this->getCompanyId();
        $feedback = CompanyFeedback::where('company_id', $companyId)
            ->findOrFail($id);

        $feedback->update($request->only(['title', 'message']));

        return response()->json($feedback);
    }

    public function destroy(string $id)
    {
        $companyId = $this->getCompanyId();
        $feedback = CompanyFeedback::where('company_id', $companyId)
            ->findOrFail($id);

        $feedback->delete();

        return response()->noContent();
    }

    public function adminIndex()
    {
        $feedback = CompanyFeedback::with('company')
            ->latest()
            ->paginate(15);

        return response()->json($feedback);
    }
}
