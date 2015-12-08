# A PHP Filter for Haml. This simply wraps code inside <?php ?> tags. While this
# may seem like a strange idea, some people use Haml to generate mostly static
# HTML documents that then include small amounts of PHP.
#
# This code also serves as an example of how to implement a simple filter for
# Haml.
module Haml
  module Filters
    module PHP
      include Base

      def render(text)
        "<?php\n  %s\n?>" % text.rstrip.gsub("\n", "\n  ")
      end
    end
  end
end

#an extension to allow specification of safe strings
#see http://stackoverflow.com/a/16617011/49540
class String
  def html_safe?
    defined?(@html_safe) && @html_safe
  end

  def html_safe
    @html_safe = true
    self
  end
end
require 'haml/helpers/xss_mods'
module Haml::Helpers
  include Haml::Helpers::XssMods
end