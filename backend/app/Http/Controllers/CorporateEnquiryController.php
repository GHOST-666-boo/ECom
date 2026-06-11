<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Models\CorporateEnquiry;
use Illuminate\Support\Facades\Log;

class CorporateEnquiryController extends Controller
{
    /**
     * Handle corporate gifting enquiry form submission.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function submit(Request $request)
    {
        $validated = $request->validate([
            'company_name'   => 'required|string|max:255',
            'company_email'  => 'required|email|max:255',
            'contact_number' => 'required|string|max:20',
            'categories'     => 'nullable|array',
            'categories.*'   => 'string|max:100',
            'message'        => 'nullable|string|max:500',
        ]);

        try {
            // Save to database
            $enquiry = CorporateEnquiry::create($validated);

            // Send email notification to admin
            $adminEmail = config('mail.from.address', 'himanshuverma231996@gmail.com');

            Mail::send('emails.corporate-enquiry', ['enquiry' => $validated], function ($message) use ($validated, $adminEmail) {
                $message->to($adminEmail)
                    ->subject('New Corporate Gifting Enquiry from ' . $validated['company_name'])
                    ->replyTo($validated['company_email'], $validated['company_name']);
            });

            Log::info('Corporate enquiry submitted successfully', [
                'company_name'  => $validated['company_name'],
                'company_email' => $validated['company_email'],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Your corporate gifting enquiry has been submitted successfully! We will get back to you soon.',
            ], 200);

        } catch (\Exception $e) {
            Log::error('Corporate enquiry submission failed', [
                'error' => $e->getMessage(),
                'data'  => $validated,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to submit your enquiry. Please try again later.',
            ], 500);
        }
    }
}
