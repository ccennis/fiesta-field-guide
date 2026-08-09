# Prompt Log

**Total build time:** _[fill in at the end]_ across _[n]_ sittings.

This is the primary deliverable for the assessment. It records each prompt for a
coding agent to build this system, what I expected at each step, and where I had to
correct it.

## How I worked

1. **Constraints before prompts.** Before the first prompt I committed a `CLAUDE.md`
   to the repo defining code conventions, guardrails, and a rule that the agent must
   present options and wait rather than making implementation decisions on its own.
   Some of the steering in this project happened before any prompt existed.
2. **Critic before generator.** The first prompt deliberately forbids code. I used the
   model to attack my own design first, because reviewing a design is where it's
   strongest and inventing a domain model is where it's weakest.
3. **Model first, data second.** I staged my real collection data in the repo but
   withheld it from the agent until the schema was settled, so the model would be
   designed on its merits rather than mirroring the shape of my spreadsheet.
4. **Real data, real MESS.** The seed data is my actual Fiestaware collection
   spreadsheet, imported as-is. I did not clean it first. The inconsistencies in it are
   the point.
5. **Logged as I went.** Each prompt was written into this file before it was sent, so
   the "what I expected" field is a prediction rather than hindsight.

## What is and isn't captured here

The agent's full responses are not reproduced. This log records the prompt, my reasoning,
and my assessment of what came back. Where the output itself is the evidence, a short
verbatim excerpt is included.

Entries are not edited after the fact. Where later work proved an earlier prompt was
poorly framed, a `Retrospective` note is appended to that entry rather than rewriting it.

The judgment fields in every entry are mine, not the agent's.

## Entry format

```
## Prompt NN - <short title>
**Time:**
**Phase:**

**Prompt (verbatim):**
>

**Why I phrased it this way:**

**What I expected:**

**What it did well:**

**What it did poorly:**

**Follow-up / correction:**
```

---

# Before the build

The numbered prompts below are the build. This section covers how I got to the starting
line, because the design did not arrive out of nowhere and the reader should know what
was already settled before Prompt 01.

**Two roles, two sessions.** I used one AI conversation as a planning partner and a
separate coding agent in the project directory as the implementer. The planning session
did no coding; the coding agent was not asked to choose the problem. Keeping those apart
was deliberate. Only the implementation prompts are numbered below.

**Choosing the problem.** I considered a GPX route-stitching tool and a freight invoice
audit before landing here. _[fill in: the real reason you switched — you own this domain,
you'd catch a wrong answer instantly, and the bike version required a mental model you
didn't have. Say it in your own words.]_

**Pressure-testing the design.** I used the planning session to attack my own idea before
building it. The distinction it surfaced that mattered most was separating the **catalog**
of what Fiesta is (line, color, era, shape) from the **holdings** I personally own. That
framing came from the model, not from me. I recognized it as correct immediately, because
my own spreadsheet has been working around exactly that problem for years: every color is
stored as a string with the production years jammed inside it, `Cobalt(1936-1951)` versus
`Cobalt (1986-)`, because the color name alone was never a sufficient identity.

_[fill in: anything else you brought in already decided, in your words.]_

**Setup.** I started from an existing Laravel 13 + React 19 starter I maintain rather than a bare laravel new, so the agent had working code to match instead of inventing conventions from scratch. That partly backfired, and Prompt 01 caught it: the starter's Post slice predates my conventions doc and contradicts it in two places. I resolved it in Prompt 02 by ruling that the doc wins.

**Data staged, withheld.** Both tabs of my collection spreadsheet were exported to
`database/seed-data/` before the first prompt, uncleaned, and the agent was explicitly
told not to open them until the schema existed.

---

# Session 1 - _[date]_

_Started: · Ended: · Elapsed:_

## Prompt 01 - Design critique, no code
**Time:**
**Phase:** Intent and critique

**Prompt (verbatim):**

> I'm building a collection management tool for Fiesta, also known as "Fiestaware", the ceramic dinnerware line
> that Homer Laughlin has made since 1936. I collect it and hunt for pieces at antique
> malls, estate sales and eBay/Etsy.
>
> Two jobs it needs to do:
>
> 1. Track what I own. What piece, what color, what era, what condition, what I paid, and
>    what it is worth now, with value tracked over time rather than as a single current
>    number.
> 2. Help me identify a piece while I am standing in a shop holding it, and tell me
>    whether it is rare, roughly what it is worth, and whether I already own one.
>
> Constraints: this is a timeboxed exercise, roughly three hours of build. It should run
> fully offline with no API keys. Laravel API + React frontend on SQLite, using the
> starter already in this folder.
>
> There is real collection data in `database/seed-data/`. Do not open it yet. I want the
> data model designed on its merits before you see the shape of my spreadsheet.
>
> Do not write any code. First read `CLAUDE.md` and the existing Post slice so you know
> the conventions. Then tell me three things: where this design is weakest, what you need
> to know from me before you can model the data, and what you would cut to fit the
> timebox.

**Why I phrased it this way:**
Phrased to set groundrules and deliver expectations.

**What I expected:**
I expected to see some decision forks, and analysis of what exists and what could be made from the proposal above.

**What it did well:**
Analyzed the pain points of not having dish images but rather relying on data gathered from the user (me). It broke down the issues and delivered me a few options on where to go next with numbered questions for modeling, shown below: 

"2. What I need from you before modeling

Ordered by how much the answer changes the schema. Seven and eight are the ones I'd most hate to guess on.

1. Rarity grain — variant-level, or color-level plus a short hand-listed set of known rare variants? And do you have rarity data, or does it ship nullable?
2. Where value numbers come from — your own notes, a price guide you'll transcribe, typed in ad hoc, or "none exists yet, make it nullable"? And is the primary grain variant-level market value, per-holding appraisal, or both?
3. Identification inputs, ranked — of shape, size, color guess, and backstamp text, which do you actually have and trust in the aisle? This decides whether the funnel is shape-first or mark-first.
4. Lots — do you buy mixed boxes at estate sales, and does per-piece cost basis need to be honest about allocation, or is "I'll split it myself and type a number" fine for three hours?
5. Duplicates — one row per physical object, or one row with a quantity? Quantity breaks per-item condition and cost basis; row-per-item makes bulk entry tedious. Your call, and it cascades.
6. Line and era vocabulary — what's in scope? Vintage only, or post-86 too, and do Ironstone / Amberstone / Kitchen Kraft / commemoratives / store exclusives need to exist? Is line an axis of identity or a tag? And is era explicit or derived from color dates?
7. Does a catalog source exist outside your collection? This is the big one. If the only reference data is the collection export, then the catalog only knows about things you already own — and Job 2 is structurally impossible for any piece you don't own, which is most of them. Either there's a separate shape/color list, or the honest in-shop answer for an unowned piece is "not in catalog, unknown," and we design the UI to say that instead of implying coverage it doesn't have.
8. A convention conflict to settle: CLAUDE.md mandates Laravel Resources and gives a "good/bad" example, but the Post slice returns raw models with no app/Http/Resources/ directory at all. Same for app/Enums/. Confirm the doc wins over the starter and I'll create both — I'm not going to silently pick."

**What it did poorly:**
Eight questions is overcautious. A stronger response would have proposed a schema with assumptions marked and invited corrections,which would be faster, and easier to react to than an interrogation.

**Follow-up / correction:**


## Prompt 2 -
**Time:** 12:59
**Phase:** Intent and critique

**Prompt (verbatim):**
1. Rarity. Color level, nullable. Don't build at the variant level, I don't have data that fine and I'd be making it up. Medium Green is the one that actually matters, and I'll flag a handful by hand once the catalog exists. It ships mostly null and that's fine.

2. Value. Variant level is the primary target. A 10" plate in a given color is worth what it's worth regardless of which one of mine it is. Add in an observations table with a source field, because my numbers are a rough estimate and some duplicate. Most are a flat table I made myself ($15 dinner plate, $8 salad, $10 mug). Some are real, like the $130 Harlequin teapot and the $35 cat face mug. I want to tell those apart later. Holdings can carry a purchase price but will be null on nearly everything, my sheet did not track what I paid.

3. Identify inputs. Shape first, color second. Those are the two I can see without doing anything. Backstamp is the best clue in theory and the worst in practice as there are some valid pieces that don't have it. It should be an optional tiebreaker rather than a gate. Size I'd almost never measure

4. No I do not buy lots.

5. Duplicates. One row per physical object. Per piece condition matters more to me than fast entry, and the importer does the tedious part anyway. And where my sheet says qty 2, expand it into two rows.

6. Vocabulary, in scope: Fiesta, Riviera, Harlequin, all three already in my data. Out of scope: Ironstone, Amberstone, Kitchen Kraft, commemoratives, store exclusives (bealls, etc)

Line is part of identity. Harlequin red and Fiesta red are different objects that happen to share a word.

Era should be derived from the color's date range rather than stored on its own. My sheet already works that way - every color is a string with the years beside it.

7. Catalog source. You're right about the risk and wrong about the data. Tab 2 has seven color rows with nothing in them: Gray, Chartreuse, Medium Green, Turf Green, Pearl Gray, Evergreen, Chocolate. That sheet has been half a wanted list for years.

Build the catalog as the cross product of the axes instead of the combinations I happen to own. Roughly 48 colors with year ranges, about 20 shapes once you collapse the vocabulary, so somewhere near 960 variants against the 136 pieces I have. Most of the catalog is stuff I don't own, which is what job 2 needs.

One catch I want visible in the UI is that I have production years for colors but not for shapes, so the cross product will generate combinations that never existed. Don't let the interface imply the catalog is verified. Where there's no data, say "no known example" instead of dressing it up as a real listing.

8. Conventions- Doc wins. Create app/Http/Resources/ and app/Enums/. The Post slice is leftover scaffolding from my starter and I'm deleting it once we have our own patterns, so don't match it anywhere it contradicts CLAUDE.md. Good catch, and keep flagging things like that.

On the cut list-- Agreed on all of it except the want list. Once the catalog is a cross product and holdings are sparse, "do I own one" / "what am I missing" are the same query with the filter flipped. That should be a toggle on the collection screen. I wanted every post 86 (p86) color at one point and it's the view I'd actually use.

**Why I phrased it this way:**
Answers to questions and steering our expectations on outcomes.

**What I expected:**
I expected to see some decision forks, and analysis of what exists and what could be made from the proposal above.

**What it did well:**
the agent's shape observation catch is right. It spotted that my "variant-level" answer contradicts the data and that seeding 48 rows of one rough guess would end up with false precision.I was wrong and it caught me.


## Prompt 3 - 

On rarity, I'm changing my answer. Color level alone is not enough. Some shapes are rare in any color, covered onion soup and the syrup are hard to find at all. And the pieces I actually hunt are the overlap, a rare shape in a rare color.

Add a nullable rarity on shapes, same enum as colors. Add a nullable one on variants while you're in there. I won't populate it now, but I want the ability to boost value on something that is rare in a rare color or unverified rare but possible.

Don't compute a combined score, show the facts next to each other, "Medium Green: rare" and "covered onion soup: rare," and let me read them, same argument you made for condition and value. If variants.rarity is set, it overrides.

1. Ratified. Cross within line. Harlequin shapes are their own molds, a Harlequin teapot and a Fiesta teapot are NOT the same object.

2. Two eras. Vintage 1936 to 1973, post 86 from 1986 on. My sheet doesn't distinguish anything later

3. Vocabularies

Rarity: common, uncommon, rare, very rare.
Condition: mint, excellent, good, fair, damaged. Nothing in my sheet records condition, so this ships null on every imported row and I can fill it in over time.
ValueSource: four. default_schedule for the blanket numbers I applied ($15, $8, $10), owner_estimate for the ones I actually know, sold_comp, price_guide. The first two are the split I care about.
4. No. Don't multiply value by condition. Show them side by side.

5. Add a nullable hex on colors.

6. Accept the single point. Don't invent a second date. I'll add current numbers for eight or ten pieces I have opinions about. If I run out of time, the feature ships with this working and the data flat, and I'll say that in the writeup.

Go ahead and open database/seed-data/. Come back with the reconciliation plan and a time estimate before you write any importer code.


## Prompt 4 -
