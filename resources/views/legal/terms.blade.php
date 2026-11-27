@php($email = config('rankedlaunch.contact_email'))
<x-legal-page title="Terms of Service" description="The rules for using RankedLaunch: accounts, product submissions, fair voting, one-time payments, backlinks and liability." :updated="config('rankedlaunch.legal_updated')">
    <p>These terms apply when you use RankedLaunch (“we”, “us”), operated by {{ config('rankedlaunch.legal_name') }}. By creating an account or submitting a product you agree to them.</p>

    <h2>Accounts</h2>
    <p>You’re responsible for your account and what you do with it. One person, one account. Don’t share or sell accounts.</p>

    <h2>Submitting products</h2>
    <ul>
        <li>You must own the product or have permission to list it.</li>
        <li>Listings must be accurate. No spam, malware, scams, adult content or anything illegal.</li>
        <li>We review every listing and may decline, edit for formatting, or remove listings that break these rules.</li>
        <li>You give us permission to show your product’s name, logo, screenshots and description on RankedLaunch and in our emails and social posts.</li>
    </ul>

    <h2>Voting</h2>
    <p>Votes must come from real people. Buying votes, vote rings, fake accounts or automated voting are not allowed. We may remove votes, reset rankings or remove listings when we see manipulation, without a refund.</p>

    <h2>Payments</h2>
    <p>Paid plans are one-time payments for a single launch, processed by our payment partner, who acts as the merchant of record. Prices are shown before you pay. See our <a href="{{ route('refunds') }}">refund policy</a>.</p>

    <h2>Backlinks and rankings</h2>
    <p>Paid launches include a dofollow link from their product page. We don’t promise any specific search ranking, traffic or number of votes.</p>

    <h2>Changes and termination</h2>
    <p>We may update these terms and will change the date above when we do. We may suspend accounts that break these terms.</p>

    <h2>Liability</h2>
    <p>RankedLaunch is provided “as is”. To the extent the law allows, we’re not liable for indirect losses, and our total liability is limited to what you paid us in the last 12 months.</p>

    <h2>Contact</h2>
    <p><a href="mailto:{{ $email }}">{{ $email }}</a></p>
</x-legal-page>
