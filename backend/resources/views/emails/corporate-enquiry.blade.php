<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>New Corporate Gifting Enquiry</title>
</head>
<body style="font-family: 'Segoe UI', Tahoma, sans-serif; background-color: #f6f3f2; margin: 0; padding: 20px;">
    <div style="max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 12px rgba(0,0,0,0.08);">
        <!-- Header -->
        <div style="background: linear-gradient(135deg, #463f38 0%, #5e564f 100%); padding: 30px; text-align: center;">
            <h1 style="color: #ffffff; font-size: 22px; margin: 0; letter-spacing: 0.05em;">
                Corporate Gifting Enquiry
            </h1>
            <p style="color: rgba(255,255,255,0.7); font-size: 13px; margin-top: 6px;">
                A new enquiry has been submitted
            </p>
        </div>

        <!-- Body -->
        <div style="padding: 30px;">
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="padding: 12px 0; border-bottom: 1px solid #f0eded; color: #7e766e; font-size: 13px; width: 140px; vertical-align: top;">
                        Company Name
                    </td>
                    <td style="padding: 12px 0; border-bottom: 1px solid #f0eded; color: #1b1b1c; font-size: 14px; font-weight: 600;">
                        {{ $enquiry['company_name'] }}
                    </td>
                </tr>
                <tr>
                    <td style="padding: 12px 0; border-bottom: 1px solid #f0eded; color: #7e766e; font-size: 13px; vertical-align: top;">
                        Email
                    </td>
                    <td style="padding: 12px 0; border-bottom: 1px solid #f0eded; color: #1b1b1c; font-size: 14px;">
                        <a href="mailto:{{ $enquiry['company_email'] }}" style="color: #4c3e25; text-decoration: none;">
                            {{ $enquiry['company_email'] }}
                        </a>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 12px 0; border-bottom: 1px solid #f0eded; color: #7e766e; font-size: 13px; vertical-align: top;">
                        Contact Number
                    </td>
                    <td style="padding: 12px 0; border-bottom: 1px solid #f0eded; color: #1b1b1c; font-size: 14px;">
                        {{ $enquiry['contact_number'] }}
                    </td>
                </tr>
                @if(!empty($enquiry['categories']))
                <tr>
                    <td style="padding: 12px 0; border-bottom: 1px solid #f0eded; color: #7e766e; font-size: 13px; vertical-align: top;">
                        Categories
                    </td>
                    <td style="padding: 12px 0; border-bottom: 1px solid #f0eded; color: #1b1b1c; font-size: 14px;">
                        @foreach($enquiry['categories'] as $category)
                            <span style="display: inline-block; background: #f6f3f2; padding: 4px 12px; border-radius: 20px; font-size: 12px; margin: 2px 4px 2px 0; color: #4c3e25;">
                                {{ $category }}
                            </span>
                        @endforeach
                    </td>
                </tr>
                @endif
                @if(!empty($enquiry['message']))
                <tr>
                    <td style="padding: 12px 0; color: #7e766e; font-size: 13px; vertical-align: top;">
                        Message
                    </td>
                    <td style="padding: 12px 0; color: #1b1b1c; font-size: 14px; line-height: 1.6;">
                        {{ $enquiry['message'] }}
                    </td>
                </tr>
                @endif
            </table>
        </div>

        <!-- Footer -->
        <div style="background: #f6f3f2; padding: 20px; text-align: center;">
            <p style="color: #7e766e; font-size: 12px; margin: 0;">
                This enquiry was submitted via the Corporate Gifting page.
            </p>
        </div>
    </div>
</body>
</html>
