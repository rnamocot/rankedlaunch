@php($email = config('rankedlaunch.contact_email'))
<x-legal-page title="Privacy Policy" description="What RankedLaunch collects, why we collect it, who we share it with, and how to export or delete your data. We never sell it." :updated="config('rankedlaunch.legal_updated')">
    <p>We collect as little as we can and never sell your data.</p>

    <h2>What we collect</h2>
    <ul>
        <li><strong>Account:</strong> your name, email and, if you sign in with Google, GitHub or X, your profile photo and account ID.</li>
        <li><strong>Products you submit:</strong> everything in the submission form, which is shown publicly once your product launches.</li>
        <li><strong>Votes:</strong> which products you upvoted. The count is public; who voted is not.</li>
        <li><strong>Payments:</strong> handled by our payment partner. We only receive the order ID, amount and status, never your card details.</li>
        <li><strong>Newsletter:</strong> your email, if you subscribe.</li>
        <li><strong>Usage:</strong> anonymous view and click counts for product pages. A session cookie keeps you signed in and stops us counting the same visit twice.</li>
    </ul>

    <h2>How we use it</h2>
    <p>To run the site, email you about your launches, show makers their launch numbers in aggregate, prevent abuse, and send the newsletter if you subscribed.</p>

    <h2>Sharing</h2>
    <p>We share data only with services that help us run RankedLaunch (hosting, email delivery, payments), and only what they need.</p>

    <h2>Your choices</h2>
    <p>Ask us to export or delete your account and data at any time: <a href="mailto:{{ $email }}">{{ $email }}</a>. Every newsletter has an unsubscribe link.</p>
</x-legal-page>
