# WordPress PayPal Return Modal

A lightweight WordPress plugin that detects PayPal return query parameters, shows a "Donation Received" modal, and removes PayPal parameters (including sensitive personal information) from the URL without reloading the page.

This was developed explicitly for Whole Child Montessori Preschool, and contains code specific to that use case.  It should work generically though for catching PayPal return URLs, and provide a template for parsing out custom fields.

## What It Does

- Runs on the frontend via `wp_footer`.
- Detects common PayPal return params (for example `tx`, `txn_id`, `PayerID`, `payment_status`, `st`).
- If payment status is completed, shows a confirmation modal.
- Builds a message using donation amount (`mc_gross` or `amt`) and currency (`mc_currency` or `cc`).
- Optionally includes a family name (`option_selection1` or `os0`).
- Removes PayPal-related query params from the address bar using `history.replaceState`.

## Installation

1. Use the WordPress "Add New Plugin" menu to upload this code as a zip file.
2. Activate the new plugin.

## Usage

No shortcode or settings page is required.

After activation, visit a URL that contains PayPal return parameters, for example:

```
https://example.org/some-page/?st=Completed&tx=ABC123&amt=50.00&cc=USD
```

On page load:

- A donation confirmation modal appears (completed payment + transaction ID required).
- PayPal params are stripped from the URL in-place.

## Notes

- The script executes for all frontend pages because it is attached to `wp_footer`.
- URL cleanup is performed even when a success modal is not shown, as long as recognized PayPal params are present.
- The modal is rendered inline with basic styles and a very high z-index for visibility.
- The modal is intentionally unstyled as much as possible, to allow for the theme's styling to apply to its elements.

## File

- `wcm-paypal-toast.php`: plugin header, frontend script injection, modal behavior, and URL parameter cleanup.
