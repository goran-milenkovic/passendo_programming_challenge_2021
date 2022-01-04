# Passendo Programming Challenge
## Assumptions

Since a lot of things are not listed in the task regarding the syntax of the string from the advertiser some assumptions are taken:

- I assume that helper methods may be written despite main method
- I created class because I also wrote tests but I assume that instance variables cant be used so where they would be gladly accepted in helper methods I passed them by reference

- There may be an incorrect number of parentheses
    - More opened than closed (cover by test)
    - More closed than opened (cover by test)
- Key may not exist in the publisher array (cover by test)
- Range values may be an incorrect
    - Left range value may not be specified (cover by test)
    - Right range value may not be specified (cover by test)
- Values after signs may not be specified (cover by tests for multiple signs)
- In case of several values may not all be specified (cover by test)
- Left and right range values types (cover by test)
- Arround signs cannot be spaces (by given examples)
- Spaces are optional after of before '('', ')'', 'and', 'or'

## Idea

- Since this will probably be used as core algorithm **speed is very important** and the idea is just **one pass through the string** provided by advertiser and as more as possible **lazy calculation**
- *For maybe even better optimization I think some analysis needs to be done for advertiser string*
- if certain expressions are highly nested, ie:
1. A || (H_N_E) && ...(some more exprs)
    - A = False => A || can be popped from expression
    - A = True => **FULL EXPRESSION GOES TO TRUE**
2. A && (HIGHLY_NESTED_EXPR) && ....(some more exprs)
    - A = False => complete expr => **HIGHLY_NESTED_EXPR should not be counted** because A && (HIGHLY_NESTED_EXPR) => False
    - A = True => A && can be popped from expression

- Recursive calls for nested expressions with an additional parameter for index of first character from this expression

 