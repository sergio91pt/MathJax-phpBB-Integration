# MathJax phpBB Integration #

[![Powered by phpBB][1]][2]
[![Powered by MathJax][3]][4]

phpBB modification that enables users to post beautiful math in LaTeX and MathML, rendered in all major browsers using the MathJax Javascript Library.

## Features: ##
* Uses phpBB BBCode system.
* Multiple BBCodes can be used with static preview texts.
* Dynamic loading, saving users time and bandwith.
* MathJax CDN can be used with a local installed copy for fallback purposes.
* Processing is done client side, by the javascript library. No complicated setups!
* Works on all major browsers.
* Renders in native MathML, HTML-CSS fonts and image fonts depending on the browser capabilities.

## Requirements: ##
* phpBB 3 (3.0.9 is recommend)
* The [MathJax library][5] accessible from your forum's path or the ability to accept the [CDN TOS][6].

## Instalation: ##
* Use [AutoMOD][7] or open install_mod.xml in your browser and follow the instructions (`./contrib/modx.prosilver.en.xsl` is needed for the xml to render properly).
* Complete the instalation by running the php file mentioned, if any.
* Adittional languages, themes, optional modifications and update instructions are located in the `contrib/` folder and linked on install_mod.xml.
* You may check the [wiki][8] for optimization tricks and special use-cases.

## Notes: ##
* The master branch *is only intended for development purposes* and may be incomplete and/or broken between releases.
 * Please download a [tagged version][9] instead.

 [1]: ./contrib/images/phpbb.png
 [2]: ./contrib/images/mathjax.gif
 [3]: http://www.phpbb.com
 [4]: http://www.mathjax.org/
 [5]: http://www.mathjax.org/download/
 [6]: http://www.mathjax.org/download/mathjax-cdn-terms-of-service/
 [7]: http://www.phpbb.com/mods/automod/
 [8]: https://github.com/sergio91pt/MathJax-phpBB-Integration/wiki
 [9]: https://github.com/sergio91pt/MathJax-phpBB-Integration/archives/master