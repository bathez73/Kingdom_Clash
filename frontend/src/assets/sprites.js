// Mettez ici les chemins vers vos vrais sprites !
// Utilise Dragon Knight, Arcane Wizard pour les alliés et Shadow Lord pour les ennemis

import DragonKnightIdle from './Dragon Knight/0-stop/0 (1).gif'
import DragonKnightAttack from './Dragon Knight/3-attack/3 (1).gif'
import DragonKnightJump from './Dragon Knight/5-jump/5 (1).gif'
import DragonKnightDead from './Dragon Knight/6-dead/6 (1).gif'

import ArcaneWizardIdle from './Arcane Wizard/0-stop/0 (1).gif'
import ArcaneWizardAttack from './Arcane Wizard/1-attack/1 (1).gif'
import ArcaneWizardJump from './Arcane Wizard/3-jump/3 (1).gif'
import ArcaneWizardDead from './Arcane Wizard/5-dead/5 (1).gif'

import ShadowLordIdle from './Shadow Lord/0-stop/0 (1).gif'
import ShadowLordAttack from './Shadow Lord/1-attack/1 (1).gif'
import ShadowLordJump from './Shadow Lord/2-jump/2 (1).gif'
import ShadowLordDead from './Shadow Lord/3-dead/3 (1).gif'

// Attribution des sprites par type d'unité
export const unitSprites = {
  knight: {
    idle: DragonKnightIdle,
    attack: DragonKnightAttack,
    walk: DragonKnightJump,
    dead: DragonKnightDead
  },
  archer: {
    idle: ArcaneWizardIdle, // L'archer sera le magicien !
    attack: ArcaneWizardAttack,
    walk: ArcaneWizardJump,
    dead: ArcaneWizardDead
  },
  giant: {
    idle: DragonKnightIdle,
    attack: DragonKnightAttack,
    walk: DragonKnightJump,
    dead: DragonKnightDead
  },
  mage: {
    idle: ArcaneWizardIdle,
    attack: ArcaneWizardAttack,
    walk: ArcaneWizardJump,
    dead: ArcaneWizardDead
  },
  dragon: {
    idle: DragonKnightIdle,
    attack: DragonKnightAttack,
    walk: DragonKnightJump,
    dead: DragonKnightDead
  },
  cavalry: {
    idle: DragonKnightIdle,
    attack: DragonKnightAttack,
    walk: DragonKnightJump,
    dead: DragonKnightDead
  },
  healer: {
    idle: ArcaneWizardIdle,
    attack: ArcaneWizardAttack,
    walk: ArcaneWizardJump,
    dead: ArcaneWizardDead
  },
  hog: {
    idle: DragonKnightIdle,
    attack: DragonKnightAttack,
    walk: DragonKnightJump,
    dead: DragonKnightDead
  },
  // Shadow Lord est utilisé pour les unités ennemies !
  enemy: {
    idle: ShadowLordIdle,
    attack: ShadowLordAttack,
    walk: ShadowLordJump,
    dead: ShadowLordDead
  }
}
