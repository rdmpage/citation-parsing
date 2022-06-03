# Citation Parsing

## Introduction

Exploring citation parsing using Conditional Random Fields (CRF). Heavily influenced by [ParsCit](https://github.com/knmnyn/ParsCit) and [AnyStyle](https://anystyle.io). My main goal here is to get something simple working as a starting point for learning more about CRF. Nothing here is state of the art, for that see, e.g.:

- [Synthetic vs. Real Reference Strings for Citation Parsing, and the Importance of Re-training and Out-Of-Sample Data for Meaningful Evaluations: Experiments with GROBID, GIANT and Cora](https://arxiv.org/abs/2004.10410)
- [GIANT: The 1-Billion Annotated Synthetic Bibliographic-Reference-String Dataset for Deep Citation Parsing](http://ceur-ws.org/Vol-2563/aics_25.pdf)
- [Neural-ParsCit](https://github.com/WING-NUS/Neural-ParsCit)


## Data

`editor.html` is a simple HTML editor inspired by [MarsEdit Live Source Preview](https://red-sweater.com/blog/3025/marsedit-live-source-preview) where you can edit XML and see a live preview.

`data/core.xml` is the training data from AnyStyle (1510 references).

`dict.php` uses the dictionary that comes with ParsCit.

## CRF

For background see [Conditional random fields](https://en.wikipedia.org/wiki/Conditional_random_field). I use [CRF++: Yet Another CRF toolkit](http://taku910.github.io/crfpp/), which is also used in ParsCit.

### Heroku C++

To get CRF++ to work on Heroku we need to compile the executable. For background on this see [How to run an executable on Heroku from node, works locally](https://stackoverflow.com/questions/39685489/how-to-run-an-executable-on-heroku-from-node-works-locally) and [C++ buildpack](https://elements.heroku.com/buildpacks/felkr/heroku-buildpack-cpp).

I forked [felkr/heroku-buildpack-cpp](https://github.com/felkr/heroku-buildpack-cpp) and added it as a buildpack to my Heroku app (under the `Settings` tab). I put the source code for CRF++ into the root folder of the app (which makes things messy) then when the app is deployed CRF++ is compiled. Note that I tried simply logging in to the Heroku app:

`heroku run bash -a citation-parser`

and compiling the code. This failed with g++ errors:

```
configure: error: Your compiler is not powerful enough to compile CRF++.
```

Turns out that g++ is [only available at build time](https://devcenter.heroku.com/articles/stack-packages), hence to use g++ I need a buildpack.

The buildpack compiled the code, but when I logged into the shell the executable wouldn’t run:

```
heroku run bash -a citation-parser
./crf_learn
/app/.libs/crf_learn: error while loading shared libraries: libcrfpp.so.0: cannot open shared object file: No such file or directory
```

For whatever reason the executable is looking for a shared library which doesn’t exist. To fix this I edited the buildpack  [compile](https://github.com/rdmpage/heroku-buildpack-cpp/blob/master/bin/compile) script to set the `"LDFLAGS=--static" --disable-shared` flags for `configure`. This then compiled an executable that worked.

#### Update

I’ve now updated the [buildpack](https://github.com/rdmpage/heroku-buildpack-cpp) to use the `src` folder so that this repo is much tidier.

### Apple Silicon

CRF++ didn’t want to build using autotools, but it is available on Homebrew so we can just `brew install crf++` to get a working version which is installed in `/opt/homebrew/bin`.


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

`php parse_train.php data/core.xml` parses the training XML and outputs a `.train` file with the features and tags. Having converted the training data we now build the model using `crf_learn` in the CRF++ package:

`crf_learn data/parsCit.template data/core.train core.model`

```
.
.
.
Done!1065.81 s
```

Note the template file `data/parsCit.template` which tells CRF++ how to process the features, see [Preparing feature templates](http://taku910.github.io/crfpp/#templ).

To use the model we need to take some data and convert it into the training format. `refs_to_train.php` reads a text file with one reference string per line and outputs XML with each line enclosed in a `<title>` tag. This file can then be processed as if it were training data. 

```
php refs_to_train.php refs.txt

php parse_train.php refs.src.xml
```

Now we use our model to process the data using `crf_test`. In this case `crf_test` takes the data (each reference tagged with `<title>`) and outputs the tags based on the model. These tags are the ones we use to extracted the structured data. 

```
crf_test  -m core.model refs.src.train > out.train
```

We then convert the output (trained format) to XML, and we then can convert the XML to a “native” format (e.g., RIS for bibliographic data).

```
php parse_results_to_xml.php out.train > out.xml

php parse_results_to_native.php out.xml
```

Need to think about how to post process tags, and how to handle cases like this where a date has been inserted in the title so that we with the initial model we end up with two dates and titles:

```
<author>Aguilar, C., K. Siu-Ting, and P. J. Venegas.</author>
<date>2007.</date>
<title>The rheophilous tadpole of Telmatobius atahualpai Wiens,</title>
<date>1993</date>
<title>(Anura: Ceratophryidae).</title>
<journal>South American Journal of Herpetology</journal>
<volume>2:</volume>
<pages>165–174.</pages> 

```


## Generating additional data to use for testing or training

Take a RIS file and output Anystyle XML format:

```
php ris_to_training.php nsp.ris > nsp.xml
```

Convert to training format:

```
php parse_train.php nsp.xml
```

Add the output to `core.train` and then rebuild model:

`crf_learn data/parsCit.template core.train core.model`

Do this with each new set of training data so that we build a better model (we hope).

## Adding "fails” to training data

As above we can add items that we know have failed to our training set. Just follow the steps:

```
php parse_train.php fail.xml
```

Add the output to `core.train` and then rebuild model:

`crf_learn data/parsCit.template core.train core.model`

It is a good idea to then rename the `fail.xml` as it is now in the model, and start a new, empty `fail.xml` to collect new failures. If we keep doing this iteratively the model should improve.


## Testing

Take some references marked up in XML and generate training format.

php parse_train.php fail.xml

Run crf_test to get tags from model

crf_test  -m core.model fail.train > f.train

Output from crf_test has original tags and ones from model, so compare those

php parse_results_to_test.php f.train


## Examples 

```
Hogg, H.R. (1896). Araneidae. In B. Spencer (ed.) Report of the Horn Expedition to Central Australia. Pt. 2. Zoology. pp. 309-356. Melville, Mullen and Slade, Melbourne.
```











