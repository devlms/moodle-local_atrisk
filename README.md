# At-risk students for Moodle (local_atrisk)

[![CI](https://github.com/devlms/moodle-local_atrisk/actions/workflows/ci.yml/badge.svg?branch=main)](https://github.com/devlms/moodle-local_atrisk/actions/workflows/ci.yml)
[![PHPUnit + Behat](https://img.shields.io/endpoint?url=https%3A%2F%2Fdevlms.github.io%2Fmoodle-local_atrisk%2Fbadges%2Fmoodle.json)](https://devlms.github.io/moodle-local_atrisk/)
[![PHP coverage](https://img.shields.io/endpoint?url=https%3A%2F%2Fdevlms.github.io%2Fmoodle-local_atrisk%2Fbadges%2Fphp-coverage.json)](https://devlms.github.io/moodle-local_atrisk/php-coverage/)
[![Moodle](https://img.shields.io/badge/Moodle-4.5%20to%205.3-f98012)](https://moodledev.io/general/releases)
[![License: GPL v3](https://img.shields.io/badge/license-GPL%20v3-blue)](LICENSE)

One page per course category listing students who may be falling behind: no visit to a course for a while, missed assignment deadlines, or a course total under a threshold. Staff mark who they contacted, so nobody is chased twice and nobody is forgotten.

## What it does

| Part | Status |
| --- | --- |
| A manager opens a category and sees a student who has not opened a course in 20 days, with the reason. | Planned |
| A student with two missed assignment deadlines is listed with "2 missed". | Planned |
| A student whose course total is under the threshold is listed with their grade. | Planned |
| A student with no problems is not listed. | Planned |
| A manager marks a student as contacted with a note; the row shows who and when, and the student stays listed until the reason clears. | Planned |
| A teacher without the capability cannot open the page. | Planned |

## Install

Needs Moodle 4.5 to 5.3.

```bash
git clone https://github.com/devlms/moodle-local_atrisk.git local/atrisk          # Moodle 4.5 and 5.0
git clone https://github.com/devlms/moodle-local_atrisk.git public/local/atrisk   # Moodle 5.1 and later
```

Then visit Site administration, Notifications.

## Permissions

| Capability | Default roles | Allows |
| --- | --- | --- |
| `local/atrisk:view` | manager | See at-risk students in a category and mark them as contacted |

## Who made it

[DevLMS](https://devlms.com) builds custom Moodle plugins.

## License

GNU GPL v3 or later.
