import Avatar1 from './assets/backgrounds/Avatar kc 1.png'
import Avatar2 from './assets/backgrounds/Avatar kc 2.jpeg'
import Avatar3 from './assets/backgrounds/Avatar kc 3.jpg'
import Avatar4 from './assets/backgrounds/Avatar kc 4.png'
import Avatar5 from './assets/backgrounds/avatar kc 5.png'
import Avatar6 from './assets/backgrounds/avatar kc 6.jpg'

const avatars = [
  Avatar1,
  Avatar2,
  Avatar3,
  Avatar4,
  Avatar5,
  Avatar6,
]

export function getAvatarById(userId) {
  // Use user ID to pick a consistent avatar
  const index = (userId - 1) % avatars.length
  return avatars[index]
}

export { avatars }
