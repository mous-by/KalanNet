import { useEffect, useRef, useState } from 'react';
import { Animated, Image, StyleSheet } from 'react-native';

const SLIDES = [
  require('../assets/images/login/slide1.jpg'),
  require('../assets/images/login/slide2.jpg'),
  require('../assets/images/login/slide3.jpg'),
];

const INTERVAL_MS = 4500;
const FADE_MS = 700;

interface Props {
  onIndexChange?: (index: number) => void;
}

export default function LoginCarousel({ onIndexChange }: Props) {
  const [index, setIndex] = useState(0);
  const opacities = useRef(SLIDES.map((_, i) => new Animated.Value(i === 0 ? 1 : 0))).current;

  useEffect(() => {
    const timer = setInterval(() => {
      setIndex((current) => {
        const next = (current + 1) % SLIDES.length;
        Animated.parallel([
          Animated.timing(opacities[current], { toValue: 0, duration: FADE_MS, useNativeDriver: true }),
          Animated.timing(opacities[next], { toValue: 1, duration: FADE_MS, useNativeDriver: true }),
        ]).start();
        onIndexChange?.(next);
        return next;
      });
    }, INTERVAL_MS);

    return () => clearInterval(timer);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  return (
    <>
      {SLIDES.map((source, i) => (
        <Animated.View key={i} style={[StyleSheet.absoluteFill, { opacity: opacities[i] }]}>
          <Image source={source} style={styles.image} resizeMode="cover" />
        </Animated.View>
      ))}
    </>
  );
}

const styles = StyleSheet.create({
  image: {
    width: '100%',
    height: '100%',
  },
});
