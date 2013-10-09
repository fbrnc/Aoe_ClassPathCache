<?php

namespace Aoe\ClassPathCache;

use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ClearCommand extends AbstractMagentoCommand
{
    protected function configure()
    {
        $this
          ->setName('aoe:cpc:clear')
          ->setDescription('Clears AOE class path cache')
        ;
    }

   /**
    * @param \Symfony\Component\Console\Input\InputInterface $input
    * @param \Symfony\Component\Console\Output\OutputInterface $output
    * @return int|void
    */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->detectMagento($output);
        if ($this->initMagento()) {
            if (\Mage::helper('aoe_classpathcache')->clearClassPathCache()) {
                $output->writeln('<info>Success</info>');
            } else {
                throw new \Exception('Error while cleaning class path cache. Check system.log for details');
            }
        }
    }
}