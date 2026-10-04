{{--
    Approve / Disapprove buttons for a quote email.

    Tables and inline styles only: Gmail drops <style> blocks in some views
    and ignores gradients, flex and margins on links, so this is the form
    that renders as two tappable buttons everywhere. The links open a
    confirmation page — a link scanner pre-fetching them changes nothing.

    Expects $approveUrl, $declineUrl and $detailsUrl.
--}}
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin: 24px 0;">
    <tr>
        <td align="center">
            <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                <tr>
                    <td align="center" bgcolor="#059669" style="border-radius: 8px;">
                        <a href="{{ $approveUrl }}" target="_blank" style="display: inline-block; padding: 14px 28px; font-family: Arial, sans-serif; font-size: 16px; font-weight: bold; color: #ffffff; text-decoration: none; border-radius: 8px;">&#10003;&nbsp; Approve Quote</a>
                    </td>
                    <td width="12" style="font-size: 0; line-height: 0;">&nbsp;</td>
                    <td align="center" bgcolor="#e11d48" style="border-radius: 8px;">
                        <a href="{{ $declineUrl }}" target="_blank" style="display: inline-block; padding: 14px 28px; font-family: Arial, sans-serif; font-size: 16px; font-weight: bold; color: #ffffff; text-decoration: none; border-radius: 8px;">&#10005;&nbsp; Disapprove</a>
                    </td>
                </tr>
            </table>
            <p style="margin: 12px 0 0; font-family: Arial, sans-serif; font-size: 12px; color: #666666;">
                <a href="{{ $detailsUrl }}" target="_blank" style="color: #667eea;">View full quote details</a>
            </p>
        </td>
    </tr>
</table>
