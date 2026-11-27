@php($email = config('rankedlaunch.contact_email'))
<x-legal-page title="About" description="RankedLaunch is a daily launch board where indie makers get early users, a backlink that lasts, and a badge to prove it.">
    <p class="text-lg !text-ink">RankedLaunch is a daily launch board for people who build software.</p>
    <p>Every day at 00:00 UTC a new set of products goes live. The community upvotes the ones they like, and the best of the day and week earn a badge for their site. Every launch keeps a permanent page, so the work you put into launch day keeps paying off.</p>
    <p>We keep it simple: a real person reviews every listing, pricing is a one-time payment, and makers get honest numbers about what their launch did.</p>
    <h2>Contact</h2>
    <p>Questions, feedback or partnership ideas: <a href="mailto:{{ $email }}">{{ $email }}</a>. You can also find us on <a href="https://x.com/rankedlaunch" rel="noopener" target="_blank">X</a>.</p>
</x-legal-page>
