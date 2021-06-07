# Citation Parsing

Exploring citation parsing using Conditional Random Fields (CRF). Heavily influenced by [ParsCit](https://github.com/knmnyn/ParsCit) and [AnyStyle](https://anystyle.io). My main goal here is to get something simple working as a starting point for learning more about CRF. Nothing here is state of the art, for that see, e.g.:

- [Synthetic vs. Real Reference Strings for Citation Parsing, and the Importance of Re-training and Out-Of-Sample Data for Meaningful Evaluations: Experiments with GROBID, GIANT and Cora](https://arxiv.org/abs/2004.10410)
- [GIANT: The 1-Billion Annotated Synthetic Bibliographic-Reference-String Dataset for Deep Citation Parsing](http://ceur-ws.org/Vol-2563/aics_25.pdf)
- [Neural-ParsCit](https://github.com/WING-NUS/Neural-ParsCit)


## Data

`editor.html` is a simple HTML editor inspired by [MarsEdit Live Source Preview](https://red-sweater.com/blog/3025/marsedit-live-source-preview) where you can edit XML and see a live preview.

`data/core.xml` is the training data from AnyStyle.

`dict.php` uses the dictionary that comes with ParsCit.

## CRF

For background see [Conditional random fields](https://en.wikipedia.org/wiki/Conditional_random_field). I use [CRF++: Yet Another CRF toolkit](http://taku910.github.io/crfpp/), which is also used in ParsCit.

## Use

To train model we need some data that has been marked up. I follow AnyStyle’s XML, e.g.:

```
<?xml version="1.0" encoding="UTF-8"?>
<dataset>
  <sequence>
    <author>Heidegger M.,</author>
    <date>1927,</date>
    <title>Être et temps,</title>
    <editor>Gallimard, Ed.</editor>
    <date>1986,</date>
    <location>Paris.</location>
  </sequence>
.
.
.
</dataset>
```

We need to convert this into the format expected by CRF, which is one token per line, with features following, and then the tag indicating what part of the sequence this token belongs to.

`php parse_train.php data/core.xml` parses the training XML and outputs a `.train` file with the features and tags. Having converted the training data we now build the model using CRF++:

`crf_learn data/parsCit.template core.train core.model`

Note the template file `data/parsCit.template` which tells CRF++ how to process the features, see [Preparing feature templates](http://taku910.github.io/crfpp/#templ).






