

Tideman Election Dataset 
===========================
Computed under Condorcet PHP, with all methods and mods.
---------
---------------------------------------

This repository includes all original and untouched Tideman datasets on [Input Folder](Input_Tideman_Election_Collection/).  
The PHP code for computing and exporting results in the [src folder](src), dependency with Condorcet PHP with composer.json.  

### The results are pre-computed on this repository in two mods:
* Implicit Ranking _(unranked candidates on a ballot are evaluated on a new last rank)_ ** [Summary of the result](implicitRankingEvaluationOfVotes-summary.md)** (large table)
* Explicit Ranking _(unranked candidates on a ballot are ignored)_ ** [Summary of the result](explicitRankingEvaluationOfVotes-summary.md)**

_Unfortunatly, tab rendering in Github are now very readable actually_

### Detailed results and stats can be found in the [Results Output](Results_Output/) folder

For each mod, with ranking and stats for each method and his pairwise computation.. Stats from directly from [Condorcet PHP](https://github.com/julien-boudry/Condorcet).   
You will also find the votes in the [Condorcet aggregated format](https://github.com/julien-boudry/Condorcet/blob/master/Documentation/Election%20Class/public%20Election--getVotesListAsString.md), which are really easier to read. 

_* Kemeny-Young method result & stats file are under Git LFS. You must download (no preview) on Github or pull from LFS with Git._

---------------------------------------
_All default options and variants can be located on the [Condorcet PHP](https://github.com/julien-boudry/Condorcet) documentation._

---------------------------------------
> Main Author: [Julien Boudry](https://www.linkedin.com/in/julienboudry/)   
> License: [MIT](LICENSE.txt) except for Tideman Collection Input files _- Please say hello if you like or use this code!_   
> Donation: **₿ [bc1qesj74vczsetqjkfcwqymyns9hl6k220dhl0sr9](https://blockchair.com/bitcoin/address/bc1qesj74vczsetqjkfcwqymyns9hl6k220dhl0sr9)** or **[Github Sponsor Page](https://github.com/sponsors/julien-boudry)**  
> _You can also offer me a good bottle of wine, one of burgundy. _  