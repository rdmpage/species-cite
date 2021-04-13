# Species Cite


## Related projects

https://index.globalnames.org


## LSIDs

### Bugs

https://lsid.herokuapp.com/urn:lsid:organismnames.com:name:3886289/jsonld `tcom:PublishedIn` is an array as we have two publications.


## Make name database file

For each database generate a TSV file of the form
```
taxon name<tab>namespace:name id<tab>wikidata id
```
Where `taxon name` is the taxon name without authorship, `name id` is the local name identifier (i.e., without the the LSID prefix), and `wikidata id` is the Wikidata QID for the publication of that name (if known). Use IFNULL to avoid adding `NULL` to output.

The file does NOT have a header row.


### IPNI

```
SELECT Full_name_without_family_and_authors, CONCAT("ipni:", Id), IFNULL(wikidata,'') FROM names WHERE Full_name_without_family_and_authors IS NOT NULL AND Full_name_without_family_and_authors <> "";
```

### ION

```
SELECT nameComplete, CONCAT("ion:", id), IFNULL(wikidata,'') FROM names;
```

### Index Fungorum

```
SELECT nameComplete, CONCAT("if:", id), IFNULL(wikidata,'') FROM names_indexfungorum WHERE nameComplete IS NOT NULL;
```

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

### Merging

```
sort -m if.tsv ipni.tsv ion.tsv > names.tsv
``` 

## Wikidata

### bugs

Order of authors broken, e.g. Q103823100 has correct order, but by time displayed authors are reversed:

Puffinus bannermani

TOM IREDALE, & GREGORY M. MATHEWS. (1915). On some Petrels from the North-East Pacific Ocean. Ibis, 57(3), 572–609. https://doi.org/10.1111/j.1474-919x.1915.tb08206.x

## Unpaywall


### Bugs

Note that if BHL has content we don’t (might not?) have a PDF, e.g. http://localhost/~rpage/species-cite-o/?q=Calyptophilus DOI  https://doi.org/10.2307/4067266 we have 

```
"best_oa_location": {
    "host_type": "repository", 
    "oa_date": null, 
    "version": "submittedVersion", 
    "repository_institution": "Smithsonian Institute - Biodiversity Heritage Library", 
    "evidence": "oa repository (via OAI-PMH doi match)", 
    "url": "https://www.biodiversitylibrary.org/part/87693", 
    "license": "pd", 
    "updated": "2021-03-07T20:13:10.642363", 
    "endpoint_id": "q6caunfjwsunh6wiqwpg", 
    "url_for_landing_page": "https://www.biodiversitylibrary.org/part/87693", 
    "pmh_id": "oai:biodiversitylibrary.org:part/87693", 
    "url_for_pdf": null, 
    "is_best": true
  },
```
