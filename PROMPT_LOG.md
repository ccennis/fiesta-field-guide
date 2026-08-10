# Prompt Log

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

**Pressure-testing the design.** I used the planning session to attack my own idea before
building it. The distinction it surfaced that mattered most was separating the **catalog**
of what Fiesta is (line, color, era, product/shape) from the **holdings** I personally own. That
framing came from the model, not from me. I recognized it as correct immediately, because
my own spreadsheet has been working around exactly that problem for years: every color is
stored as a string with the production years jammed inside it, `Cobalt(1936-1951)` versus
`Cobalt (1986-)`, because the color name alone was never a sufficient identity.

**Setup.** I started from an existing Laravel 13 + React 19 starter I maintain rather than a bare laravel new, so the agent had working code to match instead of inventing conventions from scratch. That partly backfired, and Prompt 01 caught it: the starter's Post slice predates my conventions doc and contradicts it in two places. I resolved it in Prompt 02 by ruling that the doc wins.

**Data staged, withheld.** Both tabs of my collection spreadsheet were exported to
`database/seed-data/` before the first prompt, uncleaned, and the agent was explicitly
told not to open them until the schema existed.
## Prompt 01 - Design critique, no code

**Prompt:**

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
Prompt 2. I answered all eight questions in one message rather than iterating.

## Prompt 2 -

**Prompt:**
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
the agent's product observation catch is right. It spotted that my "variant-level" answer contradicts the data and that seeding 48 rows of one rough guess would end up with false precision.I was wrong and it caught me. It also caught that owning a piece is evidence a variant existed, and turned my request for
a UI disclaimer into a `confirmed`/`unconfirmed` field. Better answer than the one I asked for.


**What it did poorly:**
It also declined to relitigate rarity, which I
then overruled in Prompt 03. Was refusing to revisit a settled decision right or annoying?

**Follow-up / correction:**
Prompt 3, where I reversed my own rarity ruling.

## Prompt 3 -

**Prompt:**
On rarity, I'm changing my answer. Color level alone is not enough. Some products are rare in any color, covered onion soup and the syrup are hard to find at all. And the pieces I actually hunt are the overlap, a rare product in a rare color.

Add a nullable rarity on products, same enum as colors. Add a nullable one on variants while you're in there. I won't populate it now, but I want the ability to boost value on something that is rare in a rare color or unverified rare but possible.

Don't compute a combined score, show the facts next to each other, "Medium Green: rare" and "covered onion soup: rare," and let me read them, same argument you made for condition and value. If variants.rarity is set, it overrides.

1. Ratified. Cross within line. Harlequin products are their own molds, a Harlequin teapot and a Fiesta teapot are NOT the same object.

2. Two eras. Vintage 1936 to 1973, post 86 from 1986 on. My sheet doesn't distinguish anything later

3. Vocabularies

Rarity: common, uncommon, rare, very rare.
Condition: mint, excellent, good, fair, damaged. Nothing in my sheet records condition, so this ships null on every imported row and I can fill it in over time.
ValueSource: four. default_schedule for the blanket numbers I applied ($15, $8, $10), owner_estimate for the ones I actually know, sold_comp, price_guide. The first two are the split I care about.
4. No. Don't multiply value by condition. Show them side by side.

5. Add a nullable hex on colors.

6. Accept the single point. Don't invent a second date. I'll add current numbers for eight or ten pieces I have opinions about. If I run out of time, the feature ships with this working and the data flat, and I'll say that in the writeup.

Go ahead and open database/seed-data/. Come back with the reconciliation plan and a time estimate before you write any importer code.


**Why I phrased it this way:**
I had ruled color-level rarity and it was wrong. Some products are
rare in any color, and the pieces I hunt are the overlap. Adding nullable rarity on products and on variants was about leaving a seam I could populate later without a migration.

**What I expected:**
I expected it to open the seed data, tell me the two tabs broadly agreed, and give me an importer estimate. I thought reconciliation would be a small job.

**What it did well:**
It parsed both files instead of eyeballing them, and proved me wrong about my own data. I said seven matrix rows with no pieces were a want list. I own four of them, including Medium Green, the color I had just told it mattered most. It also found my
matrix totals row undercounts itself by four pieces, because cells like `1 - ball` are text
and Excel's SUM skips them.

It disclosed a time estimate over budget, 200 minutes against my 180, and offered a trimmed version rather than quietly cutting scope.

**What it did poorly:**
It revised its own earlier claim that per-line crossing would shrink the catalog. Right to correct it, but the first estimate was confident and wrong.

**Follow-up / correction:**
Prompt 4. It came back with the reconciliation report and seven blocking decisions.

## Prompt 4 -

**Prompt:**
1.) tab authority confirmed. 2.) The detail tab is real and those are colors I own. 3. ) Line years ship null. Don't copy Fiesta's dates onto Harlequin and Riviera rows. Those eleven holdings won't have an era until I fill the dates in and       
that's fine 4.) Red stays two colors. The matrix row has no line marker so it's Fiesta. The detail row is flagged Harlequin. Same word, different objects. Keep the other four aliases since those are spelling drift inside one line. 5.) I will     
approve as you send through. 6.) Treat variants as existing and allow nulls for edits later. 7.) Build trimmed.


**Why I phrased it this way:**
It prompted a series of questions asking me if I wanted tabs to be the authority (showing each in its own lane). I clarified that my detail tab on the spreadsheet was the more accurate, and answered that there are indeed 2 shades of red. Variants are told to be taken as written and I prefer trimmed down versions to approve as we went.

**What I expected:**
I expected the agent to take all this on board and finally start the coding process

**What it did well:**
It obeyed my Claude.MD and came back to me with clarifying questions vs making assumptions.

**What it did poorly:**
It was starting to skew pedantic about some of the details, which gets more and more confusing the deeper into semantics it gets.

**Follow-up / correction:**
None. It started building.

## Prompt 5 -

**Prompt:** Screenshot of the table added, along with:
this layout is bland and lacks fun and creativity. the previous images show how bright and colorful the plates and dishware is. additionally the table should span the full
width of the page and allow filtering to check by year, color, product, and whether it exists in my collection.


**Why I phrased it this way:**
my first instinct on seeing the bland UI was to add in some photos from the manufacturers site to punch up the UI but that does run the risk of putting someone else's copyrighted images in a public repo. The real cause was simpler. Zero of 63 colors had a hex value, because the column existed and I had never filled it or provided data, so every swatch rendered as an empty placeholder. 

**What I expected:**
A wider table with some attempt at design, whereas before it was purely data driven

**What it did well:**
It did create a more pleasing color palette as well as making my table wider and more functional

**What it did poorly:**
I asked for two different things in one message, a vague complaint about the look and a specific list of filters. It answered both, but I could not tell which half of the prompt drove which part of the result. Splitting them would have given me two clean signals instead of one muddy one.

**Follow-up / correction:**
Prompt 6, once I worked out the swatches were empty because I had never supplied hex.

## Prompt 6 -
**Prompt:**
I have sourced a list of unofficial but community-accepted hex codes for the fiesta colors. It lives in database/seed-data/color-hex.csv. Update the site to show these items.


**Why I phrased it this way:**
I wanted the agent to upload the hex colors to make the site more pleasing

**What I expected:**
Color hexes added to the empty inputs

**What it did well:**
It found and matched up the color names to their corresponding hexes

**What it did poorly:**
It did not call out that several colors were missing and left them blank

**Follow-up / correction:**
None at the time. The missing colors surfaced later when I checked the data myself, which is the problem. It was actually a good opportunity to test editing and adding hexes.

## Prompt 7 - 

**Prompt:**
I notice "cat-face black" in the color list, when its really a variant and truly a holiday-special novelty built on fiesta. We should add a category/filter for decal fiesta holiday items as they can be rare and collectible in their own right. Additionally, you are using the british spellings such as "colour" and should not be.


**Why I phrased it this way:**
I combined two visual mistakes and allowed the agent to respond to each in turn

**What I expected:**
All britishisms were removed and the agent came up with a way to store holiday variants as a type

**What it did well:**
It astutely created a new category for the decal versions of fiesta

**What it did poorly:**
It left many British spellings that I had to manually find and replace

**Follow-up / correction:**
None. I found and replaced the remaining British spellings by hand.

## Prompt 8 -
**Prompt:**
 Each row in the main table under the tab "Everything" should have an actions icon that allows me to edit the Hex value and add new items that I own in that color.                  


**Why I phrased it this way:**
I expected a typical table icons on the side that allow edit/view/delete and did not feel I needed to be more explicit as its a typical pattern

**What I expected:**
A sidebar of action items

**What it did well:**
It did give me a way to edit under a vertical ellipses

**What it did poorly:**
It did not come back to me with options as the CLAUDE.MD directs. Edit was not part of the modal that opens when you select an item.

**Follow-up / correction:**
Prompt 10, after I found the picker was hardcoded to plates.

## Prompt 9 -

**Prompt:**
VariantController@index violates the controllers rule in CLAUDE.md. Move the enum casting and boolean coercion into the service or a form request, and move the paginated response product into a resource instead of building the meta array by hand. CatalogController is the pattern to match. Then add the filters to the cleaned-up version, not on top of what's there.


**Why I phrased it this way:**
I wanted to remind the agent that we have directions on code norms and wanted it to find and correct them

**What I expected:**
I expected it to fix this and other outlying controllers

**What it did well:**
It did move the code into a service as I expected

**What it did poorly:**
It should not have needed asking. The thin controller rule was in CLAUDE.md before the first prompt and three of the four controllers followed it. The convention held where the code was written early and slipped where it grew. A standing document sets the default, it does not enforce it.

**Follow-up / correction:**
Prompt 11. The same convention problem in a different place.

## Prompt 10 -

**Prompt:**
You added the actions to add hex colors and select an item I already own per fiesta color but hard-coded it to plates. I should be able to choose from any "product" of fiesta from carafe to plate to T&J mug. Additionally, lets proper case all those everywhere so they don't look sloppy.


**Why I phrased it this way:**
I wanted it to correct the decision it made to offer only one type of product as well as correct the naming conventions

**What I expected:**
I expected it to create a picklist of all available product names for me to choose from

**What it did well:**
It did end up adding all products to a list for me

**What it did poorly:**
It did not catch that it was full of duplicates and basically free text updates on the tables

**Follow-up / correction:**
Prompt 12. Duplicates in the product list showed the real problem was structural.

## Prompt 11 -
**Prompt:**
You have validators out in the open in the controllers. These should be custom FormRequests to keep our Controllers clean and isolate the form logic.

**Why I phrased it this way:**
This does not exist in the Claude.md as a provision but it probably should be. I explained the reasoning behind the ask so that it would associate it with the thin controller ask.

**What I expected:**
Large chunks of validators to be moved to form requests

**What it did well:**
All validation was moved to well-named FormRequests

**What it did poorly:**
The same failure as Prompt 9 in a different place. I caught it by reading the code, not by the agent noticing its own drift. Twice in one build is a pattern rather than an accident.

**Follow-up / correction:**
None.

## Prompt 12 - 
**Time:**
**Phase:**

**Prompt:**
I see a typo brought in from the source spread sheet (canniser) and this brings up an important structural change. We need a "products" table that houses what we're calling shapes. Carafe, Cannister, Small and T&J Mug should live here, not as strings from a spreadsheet. Use the items listed by me in the spreadsheet but make the table editable so I can go in and merge items or rename them, or add new as I learn about them. Lets add check boxes to this new screen -- the merge function should only show up when multiple are selected and they will be combined.


**Why I phrased it this way:**
I gave an example of a bad entry so that it would see it and know that it should be a data item on a table vs free text imported from a spreadsheet. I gave directions to how the product table should look/feel for UX capabilities and pave the way for more custom data updates

**What I expected:**
A tab of product data, housed separately in its own table, associated via models to the actual collection items. 

**What it did well:**
Products now exists as an editable screen so users can update the catalog items in their own time, or an admin to some future model can lock down what a parent item is called.

**What it did poorly:**
It introduced a bug while fixing the typo problem. Tidying "canniser,small" to "Cannister, Small" broke the lookup key and silently dropped three holdings, 150 down to 147. It reported it clearly and fixed it but the holdings count is what caught it, not the agent.

**Follow-up / correction:**
It reported a bug it had caused during the fix. I took the corrected version.

