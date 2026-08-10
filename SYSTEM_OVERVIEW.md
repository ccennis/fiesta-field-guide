# System Overview

## What it does

A collection manager for Fiesta dinnerware, with two jobs.

The first is tracking what items I own. Which piece, which color, which era, what condition, and what it is worth, with value recorded as dated observations rather than one number that gets overwritten.

The second is identification. I am standing in an antique mall holding a plate. The application narrows by shape and color and tells me whether it is rare, roughly what it is worth, and whether I already have one. It runs offline because that is where I actually use it.

Laravel API, React frontend, SQLite. Seeded from a real collection spreadsheet.

## Why I chose this problem

I love Fiesta, but it's a complicated beast. It has been made since 1936, it has run through roughly sixty colors across two eras, the same color name comes back decades later as a different glaze, and sister lines like Harlequin and Riviera share names without sharing molds. I have tracked mine in a spreadsheet for years and it is ad-hoc, casual accounting at best. A grid was never going to hold it.

What I want is something that wrangles that complexity into three answers: what a piece is worth, what I own, and what I am still hunting. The grail list is the part I care about most, and I have been keeping one in the margins by typing rows for pieces I do not have.

This build is phase one. Every Fiesta color with a community-accepted hex, crossed against the shapes I know about, with my own collection layered on top. Later iterations can get more ambitious, and the one I actually want to build at some point is a mobile reference guide with product images that can identify a piece in the wild. That's a much more complex problem than the one I solved here, and it needs this catalog underneath it to be worth anything.

I considered two other projects first: a tool that stitches exported GPX rides into new routes, and a freight invoice audit that checks carrier charges against contracted rates. Both were reasonable. I dropped them for the same reason. In each case I would have been learning the domain and directing the AI at the same time, and I would not have been able to tell a wrong answer from a right one quickly.

With Fiesta I can. That turned out to matter more than picking something impressive, and it paid off in a way I did not anticipate. The AI's most useful contributions were the places it read my data more carefully than I had, and I could only recognize those as correct because I knew the subject.

There was a second reason I did not see at the start. My spreadsheet had been working around this system's central problem for years. Every color is stored as a string with the production years jammed inside it, `Cobalt(1936-1951)` next to `Cobalt (1986-)`, because the color name alone was never enough to identify anything. I had been hand-encoding a composite key into a text field and calling it a color.

## Key architectural decisions

**The catalog is separate from my holdings.** A dinner plate in Medium Green and one in yellow are not the same object with a different attribute. Value belongs to the type. What I paid and what condition it is in are facts about my particular plate, not about Medium Green dinner plates in general. Without that split, the question I actually need answered in a shop, "this exists, it is worth $200, and you do not have one", cannot be expressed at all.

**The catalog is a cross product, not a list of what I own.** Colors crossed with products, within each line. If it held only combinations I already have, identification would fail for exactly the pieces it exists to help with. 1,769 catalog rows against 150 owned pieces, and most of the catalog being empty is the feature.

This over-generates, so it does not pretend otherwise. Variants carry an `existence` flag that starts unconfirmed. Owning a piece is what promotes it. Everything else reads "no known example" rather than presenting a generated row as a real listing.

**Value is a series of observations, not a column.** A single `current_value` field throws away the history the tracking feature depends on. Observations also key on shape with an optional color, because that matches how my data actually exists. My numbers are mostly a blanket schedule I applied myself, one figure per shape across dozens of colors. Keying strictly on variant would have expanded one rough guess into 48 confident-looking rows. Five blanket rows and 55 specific ones instead, tagged so I can tell them apart later.

**Line is part of identity.** Harlequin Red and Fiesta Red are different objects that share a word, and a Harlequin teapot is a different mold from a Fiesta one. So colors cross only with shapes in their own line, and the catalog is three small grids rather than one large wrong one.

**Era is derived, never stored.** It reads off the color's start year. A color with no years has no era and says so, which is the honest state for the Harlequin and Riviera rows where my source only carries Fiesta's dates.

**Identity never keys on an editable string.** I learned this the expensive way. Tidying a misspelled shape name silently dropped three holdings from the import, because the lookup key derived from the raw string while the stored value had been cased. Anything a person will edit for cosmetic reasons cannot also be the join key.

## Trade-offs I made on purpose

**SQLite and no Docker**, against my normal habit. A reviewer has to clone this and run it, so setup time is a real constraint of this particular deliverable even though it would not be one for a production system. `composer setup` then `composer dev`, nothing else.

**No photo-based identification, even though it is the end goal.** Deferred rather than rejected. It needs an API key a reviewer will not have, glaze color photographs badly under antique mall lighting, and building it now would have been a thin wrapper around someone else's model sitting on a catalog that did not exist yet. The catalog has to be right before a photo lookup means anything. Guided narrowing is deterministic, testable, and works with no signal in the back of a shop, which is where I am when I need it.

**No eBay integration.** Completed-sales data is restricted and it is a rabbit hole. Manually entered comparables and CSV import instead.

**Gaps stay visible instead of being filled convincingly.** This is the decision the whole system is organized around. Harlequin and Riviera colors have no production years because my data does not have them and copying Fiesta's across would be fabrication. Rarity is mostly null and renders as "no rarity data", never as "common". Twelve pieces have no value. Shapes carry no production years, so the column does not exist rather than sitting there empty and inviting someone to fill it. Every importer decision goes into a reconciliation report and nothing in the source data is silently resolved.

The cost is a system that looks less complete than one which guessed. That is the right trade for a tool whose whole purpose is telling me what something is worth.

**The source spreadsheet is imported dirty.** Two tabs that disagree with each other about 31 colors, four different misspellings, quantities and values recorded inconsistently. I did not clean it first. Reconciling it is the actual problem, and cleaning at source would have hidden the work.

## What I would do next

The grail list is specified and unbuilt, and it is the feature I would use most. Roughly 40 combinations I typed into my spreadsheet because I want them are currently indistinguishable from the 1,600 theoretical ones I have never seen.

After that: hex values measured from my own pieces rather than a community reference, a second round of valuations so the trend has something to draw, and the shapes I hunt but have never owned, since the shape axis is bounded by my own collection and cannot identify a covered onion soup.

Then the version I actually want, which is the mobile guide with product images that identifies a piece in the wild. Everything here is the catalog that would have to sit underneath it.
