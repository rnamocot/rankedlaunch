@php($email = config('rankedlaunch.contact_email'))
<x-legal-page title="Refund Policy" description="When you can get a refund for a paid launch on RankedLaunch: rejected listings, cancellations before launch day, and our mistakes." :updated="config('rankedlaunch.legal_updated')">
    <p>Paid launches are one-time payments. Here’s when we refund:</p>
    <ul>
        <li><strong>Rejected listing:</strong> if we don’t approve your paid launch, you get a full refund.</li>
        <li><strong>Before your launch day:</strong> changed your mind? Email us before your launch date and we’ll refund you in full.</li>
        <li><strong>Our mistake:</strong> if your launch didn’t go live as booked because of a problem on our side, we’ll refund or rebook you, your choice.</li>
    </ul>
    <p>Once your product has launched, the launch can’t be refunded, since the placement, backlink and badge have been delivered. Listings removed for breaking our <a href="{{ route('terms') }}">terms</a> (for example, vote manipulation) aren’t refunded.</p>
    <p>To ask for a refund, email <a href="mailto:{{ $email }}">{{ $email }}</a> with your product name. Refunds go back to your original payment method, usually within 5–10 business days.</p>
</x-legal-page>
