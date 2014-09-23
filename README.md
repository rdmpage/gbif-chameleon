# gbif-chameleon


Data from Hjarding et al. comparison of GBIF and expert datasets.

Hjarding, A., Tolley, K. A., & Burgess, N. D. (2014, July 10). Red List assessments of East African chameleons: a case study of why we need experts. Oryx. Cambridge University Press (CUP). [doi:10.1017/s0030605313001427](http://dx.doi.org/10.1017/s0030605313001427)

## Data

The dataset Hjarding et al. obtained form GBIF is available from figshare:

Angelique Hjarding. (2014). Endemic Chameleons of Kenya and Tanzania. Figshare. [doi:10.6084/m9.figshare.1141858](http://dx.doi.org/10.6084/m9.figshare.1141858)

![qr](https://github.com/rdmpage/gbif-chameleon/raw/master/data/qrcode.jpeg)

The GBIF search results consist of 3825 records. Those that represent endemic taxa comprise 1826 records, 478 records have no coordinates.

## Reproducibility

How many GBIF records are still in the portal?

endemic_filtered.tsv only 2 of 1826 records still in portal.

no_coordinates.tsv 241 of 478 records still in portal.

search_results.tsv 397 of 3825 records still in portal.

## Polygons

To get quick and dirty view of distributions I computed convex hulls for taxa with > 2 distinct point localities. Records with negative longitude were discarded, those with latitude > 5 had latitude negated. Convex hulls computed using [Graham Scan](http://en.wikipedia.org/wiki/Graham_scan). You can see the results at https://github.com/rdmpage/gbif-chameleon/blob/master/data/endemic_filtered.polygon.geojson




