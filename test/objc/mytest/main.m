#import <Foundation/Foundation.h>
#import "Fraction.h"

int main (int argc, const char *argv[]) {
    NSAutoreleasePool *pool=[[NSAutoreleasePool alloc] init];
    NSLog(@"Hello World!");
    Fraction *aFraction = [[Fraction alloc] init];
    Fraction *bFraction = [[Fraction alloc] init];
    [aFraction setNumerator: 1];
    [aFraction setDenominator: 4];
    [aFraction print];
    NSLog (@" =");
    NSLog (@"%g", [aFraction convertToNum]);
    [bFraction print];
    NSLog (@" =");
    NSLog (@"%g", [bFraction convertToNum]);
    [pool drain];
    return 0;
}
