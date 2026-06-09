<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Models\ContactInquiry;
use Illuminate\Support\Facades\Log;

class ContactController extends Controller
{
    /**
     * Handle contact form submission.
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function submit(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'subject' => 'required|string|max:255',
            'message' => 'required|string|max:5000',
        ]);

        try {
            // Save to database
            ContactInquiry::create($validated);

            // Send email to admin
            $adminEmail = 'himanshuverma231996@gmail.com';
            
            Mail::send('emails.contact', ['inquiry' => $validated], function ($message) use ($validated, $adminEmail) {
                $message->to($adminEmail)
                    ->subject('New Contact Inquiry: ' . $validated['subject'])
                    ->replyTo($validated['email'], $validated['name']);
            });

            Log::info('Contact form submitted successfully', [
                'name' => $validated['name'],
                'email' => $validated['email'],
                'subject' => $validated['subject'],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Your message has been sent successfully. We will get back to you soon!',
            ], 200);

        } catch (\Exception $e) {
            Log::error('Contact form submission failed', [
                'error' => $e->getMessage(),
                'data' => $validated,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send your message. Please try again later.',
            ], 500);
        }
    }
}
