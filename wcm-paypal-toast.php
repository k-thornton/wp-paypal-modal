<?php

/**
 * Plugin Name: PayPal "Thank You" Modal
 * Plugin URI: https://github.com/k-thornton/wp-paypal-modal
 * Description: Shows a thank-you modal dialog when PayPal returns with a completed payment, then strips PayPal query params.
 * Author: Kyle Thornton
 * Author URI: https://github.com/k-thornton
 * Version: 1.0.0
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Example (sanitized) return URL:
 * https://www.wholechildmontessori.org/who-we-are/?PayerID=TESTPAYERID&st=Completed&tx=TESTTXID&cc=USD&amt=50.00&payer_email=redacted%40example.com&payer_status=VERIFIED&first_name=Test&last_name=Donor&address_name=Redacted&address_street=Redacted&address_city=Redacted&address_state=OR&address_country_code=US&address_zip=00000&residence_country=US&txn_id=TESTTXID&mc_currency=USD&mc_fee=1.49&mc_gross=50.00&payment_fee=1.49&payment_gross=50.00&payment_status=Completed&payment_type=instant&handling_amount=0.00&shipping=0.00&item_name=Donation%20to%20Little%20Hands%2C%20Big%20Work&quantity=1&txn_type=web_accept&option_name1=Whole%20Child%20Family&option_selection1=Test%20Family&payment_date=2026-02-14T20%3A27%3A26Z&receiver_id=TESTRECEIVERID&notify_version=UNVERSIONED&verify_sign=REDACTED
 */
function wcm_paypal_return_toast()
{
?>
	<script>
		(() => {
			const params = new URLSearchParams(window.location.search);
			const hasPayPalParams =
				params.has('tx') ||
				params.has('txn_id') ||
				params.has('PayerID') ||
				params.has('payment_status') ||
				params.has('st');

			if (!hasPayPalParams) return;

			const status = (params.get('payment_status') || params.get('st') || '').toLowerCase();
			const txnId = params.get('tx') || params.get('txn_id') || '';
			const amountRaw = params.get('mc_gross') || params.get('amt') || '';
			const currency = params.get('mc_currency') || params.get('cc') || 'USD';
			const familyName = (params.get('option_selection1') || params.get('os0') || '').trim();

			const formatAmount = (value, curr) => {
				const n = Number.parseFloat(value);
				if (!Number.isFinite(n)) return '';
				try {
					return new Intl.NumberFormat(undefined, {
						style: 'currency',
						currency: curr
					}).format(n);
				} catch {
					return value;
				}
			};

			const showModal = (message) => {
				if (document.getElementById('wcm-paypal-overlay')) return;

				const previousOverflow = document.body.style.overflow;

				const overlay = document.createElement('div');
				overlay.id = 'wcm-paypal-overlay';
				overlay.className = 'wcm-paypal-overlay';
				overlay.setAttribute('role', 'dialog');
				overlay.setAttribute('aria-modal', 'true');
				overlay.style.cssText = 'position:fixed;inset:0;display:flex;align-items:center;justify-content:center;padding:1rem;background:rgba(0,0,0,.5);z-index:2147483647;';

				const modal = document.createElement('div');
				modal.className = 'wcm-paypal-modal';
				modal.style.cssText = 'position:relative;width:min(620px, 100%);background:#fff;padding:2rem; padding-top: 4rem; border-radius:.375rem;';

				const closeButton = document.createElement('button');
				closeButton.type = 'button';
				closeButton.className = 'wcm-paypal-close';
				closeButton.setAttribute('aria-label', 'Close');
				closeButton.textContent = '×';
				closeButton.style.cssText = 'position:absolute;top:1rem;right:.75rem;width:28px;height:28px;padding:0;border-radius:8px;font-size:20px;line-height:1;display:flex;align-items:center;justify-content:center;';

				const title = document.createElement('h2');
				title.className = 'wcm-paypal-title';
				title.textContent = 'Donation Received';

				const bodyText = document.createElement('p');
				bodyText.className = 'wcm-paypal-message';
				bodyText.textContent = message;

				const closeModal = () => {
					document.removeEventListener('keydown', onKeyDown);
					overlay.remove();
					document.body.style.overflow = previousOverflow;
				};

				const onKeyDown = (event) => {
					if (event.key === 'Escape') {
						closeModal();
					}
				};

				closeButton.addEventListener('click', closeModal);
				overlay.addEventListener('click', (event) => {
					if (event.target === overlay) {
						closeModal();
					}
				});
				document.addEventListener('keydown', onKeyDown);

				modal.appendChild(closeButton);
				modal.appendChild(title);
				modal.appendChild(bodyText);
				overlay.appendChild(modal);
				document.body.appendChild(overlay);
				document.body.style.overflow = 'hidden';

				closeButton.focus();
			};

			if (status === 'completed' && txnId) {
				const formatted = formatAmount(amountRaw, currency);
				const donationPart = formatted ? `Your donation of ${formatted}` : 'Your donation';
				const onBehalfPart = familyName ? ` on behalf of ${familyName}` : '';
				const msg = `${donationPart}${onBehalfPart} was received. Thank you for supporting Little Hands, Big Work!`;
				showModal(msg);
			}

			// Remove PayPal return params (PII) from URL without reloading.
			const paypalKeys = [
				'PayerID', 'st', 'tx', 'cc', 'amt', 'contact_phone', 'payer_email', 'payer_id', 'payer_status',
				'first_name', 'last_name', 'address_name', 'address_street', 'address_city', 'address_state',
				'address_country_code', 'address_zip', 'residence_country', 'txn_id', 'mc_currency', 'mc_fee',
				'mc_gross', 'protection_eligibility', 'payment_fee', 'payment_gross', 'payment_status',
				'payment_type', 'handling_amount', 'shipping', 'item_name', 'quantity', 'txn_type',
				'option_name1', 'option_selection1', 'payment_date', 'receiver_id', 'notify_version', 'verify_sign',
				'os0'
			];

			let changed = false;
			for (const key of paypalKeys) {
				if (params.has(key)) {
					params.delete(key);
					changed = true;
				}
			}

			if (changed) {
				const qs = params.toString();
				const newUrl = window.location.pathname + (qs ? '?' + qs : '') + window.location.hash;
				history.replaceState({}, document.title, newUrl);
			}
		})();
	</script>
<?php
}
add_action('wp_footer', 'wcm_paypal_return_toast', 99);
