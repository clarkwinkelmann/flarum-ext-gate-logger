# Gate Logger (for developers)

[![MIT license](https://img.shields.io/badge/license-MIT-blue.svg)](https://github.com/clarkwinkelmann/flarum-ext-gate-logger/blob/master/LICENSE.md) [![Latest Stable Version](https://img.shields.io/packagist/v/clarkwinkelmann/flarum-ext-gate-logger.svg)](https://packagist.org/packages/clarkwinkelmann/flarum-ext-gate-logger) [![Total Downloads](https://img.shields.io/packagist/dt/clarkwinkelmann/flarum-ext-gate-logger.svg)](https://packagist.org/packages/clarkwinkelmann/flarum-ext-gate-logger) [![Donate](https://img.shields.io/badge/paypal-donate-yellow.svg)](https://www.paypal.me/clarkwinkelmann)

This extension logs information about all calls to Flarum's Gate to the default Flarum logger with `debug` priority.

This means all calls to `User::can()`, `User::cannot()`, `User::assertCan()`, etc. But not `User::hasPermission()`.

This creates *a lot* of logs, so it's probably best to only enable the extension right when you want to debug something.

There is no user-facing interface. It only logs to a file (in `storage/logs` with default logger).

Performance impact will largely be related to how fast the logger can write to disk.
If you are using a custom log driver that doesn't write to disk, performance impact might be negligible, as the logic is otherwise identical to native Flarum.

Caveats (not permanent, only when the extension is enabled):

- The extension completely replaces Flarum's Gate class with a custom one, so as new Flarum updates get released, this extension might cause Flarum to behave differently than a standard installation, or could be missing important security fixes!
- If an extension overrides `AbstractPolicy::checkAbility()` in its policy class, the method will never be called!

## Log format

The first line of the log should be prefixed by the time and debug level automatically (this is added by the logger library).

The first line will then show the gate ability name, followed by information about the actor (username or `[Guest]`) and the gate parameter (model class and ID or `[GLOBAL]` for global policies)

The next lines will show every policy class and method that are resolved for those parameters.
After each policy the output will be shown (`ALLOW`, `FORCE_ALLOW`, `DENY`, `FORCE_DENY`, `[TRUE]`, `[FALSE]` or `[NULL]`)
When a method exists on the policy with the ability name and returns non-null, the `can` method on the policy will be skipped.
In this case the output will be shown as `SKIPPED`.

The final line will show the computed decision and which method lead to that decision.
It will be one of `(Criteria Priority: <which criteria stopped was matched first>)` if any of the policy classes returned non-null, `(Admin role)` if the user is admin, `(Has permission: key)` if the ability name defaulted to a permission key, or `(Default)` if nothing was matched.

If some gate calls are nested, they will appear as separate entries in the order in which the Gate return statements were reached.

Some examples of the logs that will be created:

```
[YYYY-MM-DD HH:mm:ss] flarum.DEBUG: Gate Logger addToDiscussion(Actor: Admin, Flarum\Tags\Tag: 2)
Flarum\Tags\Access\TagPolicy@addToDiscussion: [TRUE] => ALLOW
Flarum\Tags\Access\TagPolicy@can: SKIPPED
Flarum\Approval\Access\TagPolicy@addToDiscussion: [TRUE] => ALLOW
Decision: ALLOW (Criteria Priority: ALLOW)
[YYYY-MM-DD HH:mm:ss] flarum.DEBUG: Gate Logger administrate(Actor: Admin, [GLOBAL])
Flarum\Tags\Access\GlobalPolicy@can: [NULL]
Decision: ALLOW (Admin role)
[YYYY-MM-DD HH:mm:ss] flarum.DEBUG: Gate Logger clarkwinkelmann-author-change.edit-date(Actor: OneModerator, [GLOBAL])
Flarum\Tags\Access\GlobalPolicy@can: [NULL]
Decision: ALLOW (Has permission clarkwinkelmann-author-change.edit-date)
[YYYY-MM-DD HH:mm:ss] flarum.DEBUG: Gate Logger addToDiscussion(Actor: [Guest], Flarum\Tags\Tag: 1)
Flarum\Tags\Access\TagPolicy@addToDiscussion: [FALSE] => DENY
Flarum\Tags\Access\TagPolicy@can: SKIPPED
Flarum\Approval\Access\TagPolicy@addToDiscussion: [FALSE] => DENY
Decision: DENY (Criteria Priority: DENY)
[YYYY-MM-DD HH:mm:ss] flarum.DEBUG: Gate Logger viewHiddenGroups(Actor: [Guest], [GLOBAL])
Flarum\Tags\Access\GlobalPolicy@can: [NULL]
Decision: DENY (Default)
```

## Installation

    composer require clarkwinkelmann/flarum-ext-gate-logger

## Support

This extension is under **minimal maintenance**.

It was developed for a client and released as open-source for the benefit of the community.
I might publish simple bugfixes or compatibility updates for free.

You can [contact me](https://clarkwinkelmann.com/flarum) to sponsor additional features or updates.

Support is offered on a "best effort" basis through the Flarum community thread.

## Links

- [GitHub](https://github.com/clarkwinkelmann/flarum-ext-gate-logger)
- [Packagist](https://packagist.org/packages/clarkwinkelmann/flarum-ext-gate-logger)
- [Discuss](https://discuss.flarum.org/d/29351)
