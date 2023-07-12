

Tideman Election Dataset 
===========================
Computed under [Condorcet PHP](https://github.com/julien-boudry/Condorcet), with all methods and variants.
---------
    
#### _From: https://rangevoting.org/TidemanData.html_
---------------------------------------

**This repository includes:**
* All original and untouched Tideman datasets in the [Input Folder](Input_TidemanElectionCollection/).  
* * And translated into [Condorcet Election Format](https://github.com/CondorcetPHP/CondorcetElectionFormat) in the [conversion folder](ConversionToCondorcetElectionFormat/)
* The PHP code for computing and exporting results in the [src folder](src), dependencies and Condorcet PHP comes with composer.json.  
* The computation results and stats are in the **[results folder](/Output_Results)**.  

### The results are pre-computed on this repository in two mods:
* Implicit Ranking _(unranked candidates on a ballot are evaluated on a new last rank)_ **[Summary of the result (implicit)](implicitRankingEvaluationOfVotes-summary.md)**
* Explicit Ranking _(unranked candidates on a ballot are ignored)_ **[Summary of the result (explicit)](explicitRankingEvaluationOfVotes-summary.md)**

_Unfortunately, tabs rendering in Github are not very readable actually_  
_Excel Table is available [here](Summary.xlsx)_

### Detailed results and stats can be found in the [Results Output](/Output_Results) folder

For each mod, with ranking and stats for each method and his pairwise computation. Stats directly from [Condorcet PHP](https://github.com/julien-boudry/Condorcet).   
You will also find the votes in the [Condorcet Election Format](https://github.com/CondorcetPHP/CondorcetElectionFormat) _(.cvotes files)_, which are much easier to read, the explicit files are strictly equal to those of the Tideman format, which is not the case for the implicit ones.  

_* Kemeny-Young method result & stats file are under Git LFS. You must download (no preview) on Github or pull from LFS with Git._

---------------------------------------
_All default options and variants can be located on the [Condorcet PHP](https://github.com/julien-boudry/Condorcet) documentation._

---------------------------------------
> Main Author: [Julien Boudry](https://www.linkedin.com/in/julienboudry/)   
> License: [MIT](LICENSE.txt) except for Tideman Collection Input files _- Please say hello if you like or use this code!_   
> Donation: **₿ [bc1qesj74vczsetqjkfcwqymyns9hl6k220dhl0sr9](https://blockchair.com/bitcoin/address/bc1qesj74vczsetqjkfcwqymyns9hl6k220dhl0sr9)** or **[Github Sponsor Page](https://github.com/sponsors/julien-boudry)**  
> _You can also offer me a good bottle of wine, one of burgundy. _  
