# Species Cite

Key idea is to have a text file of taxon name, identifier (LSID-like) and (optionally) a Wikidata QID for a bibliographic reference. Do a simple disk-based binary search of the file to find all occurrences of a taxon name, then display results. No need for a database, the file itself is the database.

## Make name database file

For each local taxonomic name database generate a TSV file of the form
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

```
sort worms-unsorted.tsv > worms.tsv
``` 

### Merging

```
sort -m if.tsv ipni.tsv ion.tsv worms.tsv > names.tsv
``` 


## Related projects

https://index.globalnames.org

