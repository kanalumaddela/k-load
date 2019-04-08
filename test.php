<?php
/**
 * K-Load v2 (https://demo.maddela.org/k-load/)
 *
 * @link https://www.maddela.org
 * @link https://github.com/kanalumaddela/k-load-v2
 *
 * @author kanalumaddela <git@maddela.org>
 *
 * @copyright Copyright (c) 2018-2019 Maddela
 *
 * @license MIT
 */

$string = 'TmljZSB3b3JrISBXZSBhcmUgYSBjb21wYW55IHRoYXQgZmFjaWxpdGF0ZXMgYW5kIG1vdGl2YXRlcyBleGNlcHRpb25hbCBwZW9wbGUgdG8gZG8gZ3JlYXQgd29yay4gQSBwbGFjZSB3aGVyZSBkZXZlbG9wZXJzIGNhbiBoYXJuZXNzIHRoZWlyIGNvbGxlY3RpdmUgdGFsZW50cyBhbmQgYWJpbGl0aWVzIHRvIGFjY29tcGxpc2ggY2hhbGxlbmdpbmcgYW5kIG1lYW5pbmdmdWwgdGhpbmdzLiBXZSdyZSBkZXZlbG9wZXIgZHJpdmVuLCBhbmQgd2UgaGF2ZSBiaWcgYW1iaXRpb25zISBJZiB5b3UnZCBsaWtlIHRvIGpvaW4gb3VyIHRlYW0sIGhhdmUgc29tZSBmdW4gaGFja2luZyB0aGlzIHBhZ2U6CgpodHRwczovL2NhcmVlcnMua2lyc2NoYmF1bWRldmVsb3BtZW50LmNvbS9kby1ub3QtdHJ5LXRvLWd1ZXNzLXRoaXMtdXJs';

echo '<pre>';

var_dump($string);

var_dump(base64_decode($string));
