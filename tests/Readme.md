# BDD ?

Here functional tests are much more a prototyping journey to write code step by step than E2E tests <br />
The behat features files are almost ordered in an increasing difficulty order and this avoids premature abstractions <br />
You may not need to read all the scenarios to understand the code <br />
The last features files could provide a quick overview

# TDD ?

Many devs don't "test" the business of their app but the code implementation of what they understood of the business
at coding-time. <br />
And this understanding improves. So the TDD approach is maybe overbought. <br />

TDD is a strange beast. The goals are not very often clearly defined. <br />
One can use it to check if the code implementation is correct. <br />
One can use it to discover algorithm or business regularities (rules) <br />

Both goals are a net positive. <br />
They share a common pattern : the practice of cognitive-easing provided by splitting everything into small problems. <br />

BUT: 
- checking implementations while you did not discover the business invariants is useless. <br /> You will have
to re-write code and tests.
- algorithm ? you don't discover algorithms by making TDD, except if you're making exercises or recruitment tests <br />
- often there is no business at all or the rules are not thinkable before a certain amount of time / reflexion / experience <br />
- TDD maybe helps being confident on your track to explore the problem. <br />
- But I prefer to get the BDD right and build the adequate abstractions <br />

# Unit testing ?
A lot of devs spend 50% of the time on writing tests they will have to change at every change in the logic or change in ticket flow <br />
That's why you won't find a lot of unit testing in this project. <br />
But it will be required, when:
-> I find a bug, I fix and I add a unit test
-> I have a critical peace of code that must never encounter an error
-> I quickly write few lines to have something done in test-first
