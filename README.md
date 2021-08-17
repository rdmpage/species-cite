# Species Cite


## Introduction

“Species Cite” takes as its inspiration the suggestion that citing original taxonomic descriptions (and subsequent revisions) would increase citation metrics for taxonomists, and give them the credit they deserve. Regardless of the merits of this idea, it is difficult to implement because we don’t have an easy way of discovering which paper we should cite. Species Cite tackles this by combining millions of taxonomic name records associated with Life Science Identifiers (LSIDs) with bibliographic data from Wikidata to make it easier to cite the sources of taxonomic names. Where possible it provides access to PDFs for articles using PDFs stored in the Internet Archive, the Wayback Machine, or Unpaywall. These can be displayed in an embedded PDF viewer. Given the original motivation of surfacing the work of taxonomists, Species Cite also attempts to display information about the authors of a taxonomic paper, such as ORCID and/or Wikidata identifiers, and an avatar for the author (via Wikidata or ResearchGate). This enables us to get closer to the kind of social interface found in citizen science projects like iNaturalist where participants are people with personalities, not simply strings of text. Furthermore by identifying people and associating them with taxa it could help us discover who are the experts on particular taxonomic groups, and also enable those people to easily establish that they are, in fact, experts.


## Demo script

- Home page with “wall” of taxonomists
- Examples of citations
- Examples of PDFs
- Examples of BHL
- Examples of people’s avatars

## Methods

Key idea is to have a text file of taxon name, identifier (LSID-like) and (optionally) a Wikidata QID for a bibliographic reference. Do a simple disk-based binary search of the file to find all occurrences of a taxon name, then display results. No need for a database, the file itself is the database.

## Make name database file

For each local taxonomic name database generate a TSV file of the form
```
taxon name<tab>namespace:name id<tab>higher taxon<tab>wikidata id<tab>BHL PageID<tab>fragment selector
```

Column | Contents
--|--
taxon name | Taxon name without authorship
name id | Local name identifier (i.e., without the LSID prefix) but with a QNAME to make it unique (e.g., “ion:”)
higher taxon | Name of a taxon that includes this name (e.g., “Aves”)
wikidata id | Wikidata Qid for publication for this name (if known)
BHL PageID | PageID for the page in BHL where the name was published (if known)
fragment selector | Selector for where in publication name occurs. Typically this will be the page in a PDF where name occurs, relative to the pages in the PDF (i.e., the first page of the PDF is page 1).

Use IFNULL to avoid adding `NULL` to the output.

The file does NOT have a header row.


### IPNI

```
SELECT Full_name_without_family_and_authors, CONCAT("ipni:", Id), IFNULL(Family,''), IFNULL(wikidata,''), IFNULL(bhl,''), IFNULL(selector,'')  
FROM names WHERE Full_name_without_family_and_authors IS NOT NULL AND Full_name_without_family_and_authors <> "";
```

### ION

```
SELECT nameComplete, CONCAT("ion:", id), IFNULL(`group`,''), IFNULL(wikidata,''), IFNULL(bhl,''), IFNULL(selector,'')  
FROM names;
```

### Index Fungorum

```
SELECT nameComplete, CONCAT("if:", id), "", IFNULL(wikidata,''), IFNULL(bhl,''), IFNULL(selector,'') 
FROM names_indexfungorum WHERE nameComplete IS NOT NULL;
```

### NZ

Use `php toCite.php` to generate data.

### WoRMS

To do

### ZooBank

To do


### Sorting

Sort each file separately, then sort merge. This means we can update an individual database and add it to the main database.

```
sort if-unsorted.tsv > if.tsv
``` 

```
sort ipni-unsorted.tsv > ipni.tsv
``` 

```
sort ion-unsorted.tsv > ion.tsv
``` 

```
sort nz-unsorted.tsv > nz.tsv
``` 

```
sort worms-unsorted.tsv > worms.tsv
``` 

### Merging


```
sort -m if.tsv ipni.tsv ion.tsv nz.tsv > names.tsv
``` 

## Installing on Heroku

To install on Heroku we need a way to get the large `names.tsv` file onto Heroku. The file is handled by Git Large File Storage (LFS) which Heroku doesn’t support by default. I used the following steps, based on [Deploying NLP Model on Heroku using Flask, NLTK, and Git-LFS](https://medium.com/analytics-vidhya/deploying-nlp-model-on-heroku-using-flask-nltk-and-git-lfs-eed7d1b22b11) by [pulkitrathi17](https://github.com/pulkitrathi17).

You need to do 3 things to integrate Git-LFS with Heroku:

1. Create a “Personal Access Token” for your Github account. Go to your Github profile ➜ Settings ➜ Developer Settings ➜ Personal access tokens ➜ Generate new token. Save this token somewhere safely. Note that you don’t need to specify any “scopes”, just create the token.

2. Add a Heroku buildpack for Git-LFS: You can add the required buildback using either Heroku CLI or Heroku dashboard. I used the dashboard, so go to the Settings tab for your app and add `https://github.com/raxod502/heroku-buildpack-git-lfs` as a build pack. Note that adding this buildpack meant that when I first deployed the app it didn’t start at all. Using `heroku logs --tail --app species-cite` I got a `error code=H14 desc="No web processes running”`. The fix was to add the PHP buildpack as well. Normally I don’t have to do this as Heroku “knows” that it is PHP because of the `composer.json` file. But having the LFS buildpack seems to break this.

3. Add config variable to your Heroku app. This step also can be done by either Heroku CLI or Heroku dashboard. The key is **HEROKU_BUILDPACK_GIT_LFS_REPO** and the value is the URL for your Github remote repo from which to download Git LFS assets. See here for details on the syntax. The URL should be like this:
`https://<token_generated_in_first_step>@github.com/<user_name>/ <remote_repo_name>.git`, hence for this repository it is `https://<token_generated_in_first_step>@github.com/rdmpage/species-cite.git`

As noted in (2) if you use the LFS build pack you need to explicitly tell Heroku that you need PHP.

## BHL

Investigating adding BHL to results and to visualisation of results.

Tricky as question is how to model BHL. Ideally I’d like to use an annotation, so we capture details of the link (e.g., where on the BHL page the name appears).

I’d though of using `@reverse` to include BHL pages, e.g.:

```
      "@id": "urn:lsid:ipni.org:names:943434-1",
      "@reverse": {
    			"oa:body": [
    				{
    					"@id": "http://annotation.com/1",
    					"oa:target" : {    					
    						"oa:source" : "https://www.biodiversitylibrary.org/page/10006061"
    					}
    		
    				}
    			]
    		},            

```

This would connect the IPNI name id as the `oa:body` of the annotation, and then I could link the BHL page URL using `oa:source` (and use `oa:selector` to give details on the string location on the page). Unfortunately I can’t figure out how to generate framed JSON-LD that includes `@reverse`. However, tools to parse JSON-LD seem to extract the correct triples. So may have to construct JSON-LD manually… :(

### ResearchGate “Wall”

```
SELECT distinct ?researchgate WHERE {
  ?work wdt:P50 ?author.
  ?author wdt:P2038 ?researchgate .
  
  #taxonomist? 
  ?article schema:about ?author .
  FILTER(regex(str(?article), "species.wikimedia.org"))
 } 
```

Then call [rdmpage/researchgate-harvester](https://github.com/rdmpage/researchgate-harvester) to retrieve image (if it exists).

## Related projects

[Global Names Index](https://index.globalnames.org)


## Issues

### Badness

- Acrostichum filare: multiple DOIs and PDFs, problem with SPARQL returning multiple values for single field :( http://localhost/~rpage/species-cite/api.php?q=Acrostichum+filare

- Tisaniba (Loeblica) loebli not found

