"Species Cite" https://species-cite.herokuapp.com takes as its inspiration the suggestion that citing original taxonomic descriptions would increase citation metrics for taxonomists, and give them the credit they deserve. Regardless of the merits of this idea, it is difficult to implement because we don’t have an easy way of discovering which paper we should cite. Species Cite tackles this by combining millions of taxonomic name records associated with Life Science Identifiers (LSIDs) with bibliographic data from Wikidata to make it easier to cite the sources of taxonomic names. Where possible it provides access to PDFs for articles using PDFs stored in the Internet Archive, the Wayback Machine, or Unpaywall. These can be displayed in an embedded PDF viewer. Given the original motivation of surfacing the work of taxonomists, Species Cite also attempts to display information about the authors of a taxonomic paper, such as ORCID and/or Wikidata identifiers, and an avatar for the author (via Wikidata or ResearchGate). This enables us to get closer to the kind of social interface found in citizen science projects like iNaturalist where participants are people with personalities, not simply strings of text. 

For the GBIF community this tool is a proof of concept of a vision of taxonomic knowledge that:

- is accessible, we should be able to find the science behind a name

- highlights further sources of data from GBIF to use (are there occurrences in these papers that aren’t in GBIF?)

- links to people currently active in taxonomy, people who could be encouraged to participate in contributing data to GBIF, and helping with data quality issues. By linking them to taxa and publications their expertise is easily demonstrable.

Entry URL: https://species-cite.herokuapp.com

Source code: https://github.com/rdmpage/species-cite

How it works

(There is a longer explanation on my blog https://iphylo.blogspot.com/2021/07/species-cite-linking-scientific-names.html)
Under the hood there's a lot of moving pieces. The taxonomic names come from a decade or more of scraping LSIDs from various taxonomic databases, primarily ION, IPNI, Index Fungorum, and Nomenclator Zoologicus. Given that these LSIDs are often offline I built https://lsid.herokuapp.com and https://lsid-two.herokuapp.com to make them accessible.

The bibliographic data is stored in Wikidata, and I've built a simple search engine to find things quickly https://wikicite-search.herokuapp.com. 

The map between names and literature his based on work I've done earlier in https://bionames.org and various unpublished projects.

To make things a bit more visually interesting I've used images of taxa from Phylopic, and also harvested images from ResearchGate to supplement the rather limited number of images of taxonomists in Wikidata.

One of the things I've tried to do is avoid making new databases, as those often die from neglect. Hence the use of Wikidata for bibliographic data. The taxonomic data is held in static files in the LSID caches. The mapping between names and publications is a single (large) tab-delimited file that is searched on disk using a crude binary search on a sorted list of taxonomic names. This means you can download the Github repository and be up and running without installing a database. Likewise the LSID caches use static (albeit compressed) files. The only database directly involved is the WikiCite search engine.

Species-Cite contains millions of taxonomic names, and while hundreds of thousands have been mapped to bibliographic identifiers, there is a lot that remains to be done. The web site displays progress bars for how many names are linked publications with to identifiers (such as DOIs), and how many are linked to items in Wikidata.



