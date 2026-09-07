import Svg, { Circle, ClipPath, Defs, G, Path, Rect } from 'react-native-svg';

// Ported directly from the web login page's inline SVG
// (resources/views/auth/login.blade.php, .brand-logo) so the mobile app
// uses the exact same mark instead of a generic placeholder icon.
export default function BrandLogo({ size = 82 }: { size?: number }) {
  const height = (size * 104) / 120;

  return (
    <Svg width={size} height={height} viewBox="0 0 120 104">
      <Path d="M25 76c14-7 28-7 42 0V42c-14-7-28-7-42 0v34z" fill="#ffffff" stroke="#0b1f3a" strokeWidth={3} strokeLinejoin="round" />
      <Path d="M95 76c-14-7-28-7-42 0V42c14-7 28-7 42 0v34z" fill="#f8fafc" stroke="#0b1f3a" strokeWidth={3} strokeLinejoin="round" />
      <Path d="M60 47v34" stroke="#d4af37" strokeWidth={4} strokeLinecap="round" />
      <Path d="M22 79c15-8 31-8 45 0M98 79c-15-8-31-8-45 0" stroke="#d4af37" strokeWidth={4} strokeLinecap="round" fill="none" />
      <Path d="M35 30l25-11 25 11-25 11-25-11z" fill="#0b1f3a" />
      <Path d="M46 36v11c8 6 20 6 28 0V36l-14 6-14-6z" fill="#d4af37" />
      <Path d="M84 31v17" stroke="#0b1f3a" strokeWidth={3} strokeLinecap="round" />
      <Circle cx={84} cy={52} r={3.5} fill="#d4af37" />
      <Circle cx={60} cy={55} r={9} fill="#0b1f3a" />
      <Path d="M49 72c5-11 17-11 22 0" fill="#0b1f3a" />
      <G transform="translate(10 10) scale(.78)">
        <Path
          d="M16 7c9 2 15 8 15 17 0 5 4 9 7 13-5 7-12 11-21 9-8-2-12-10-10-19 1-6 4-13 9-20z"
          fill="#ffffff"
          stroke="#0b1f3a"
          strokeWidth={2}
        />
        <Defs>
          <ClipPath id="mali-flag-clip">
            <Path d="M16 7c9 2 15 8 15 17 0 5 4 9 7 13-5 7-12 11-21 9-8-2-12-10-10-19 1-6 4-13 9-20z" />
          </ClipPath>
        </Defs>
        <G clipPath="url(#mali-flag-clip)">
          <Rect x={5} y={5} width={11} height={44} fill="#14b53a" />
          <Rect x={16} y={5} width={11} height={44} fill="#fcd116" />
          <Rect x={27} y={5} width={13} height={44} fill="#ce1126" />
        </G>
        <Path
          d="M16 7c9 2 15 8 15 17 0 5 4 9 7 13-5 7-12 11-21 9-8-2-12-10-10-19 1-6 4-13 9-20z"
          fill="none"
          stroke="#0b1f3a"
          strokeWidth={2}
        />
      </G>
      <Path d="M88 16c9 8 13 21 9 35M93 17c-6 4-11 10-14 19M97 51c-8-4-16-5-25-2" stroke="#0b1f3a" strokeWidth={2.5} strokeLinecap="round" fill="none" />
      <Circle cx={88} cy={16} r={3} fill="#d4af37" />
      <Circle cx={79} cy={36} r={3} fill="#d4af37" />
      <Circle cx={72} cy={49} r={3} fill="#d4af37" />
      <Circle cx={97} cy={51} r={3} fill="#d4af37" />
    </Svg>
  );
}
