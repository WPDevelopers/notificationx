/**
 * Minimal PNG decode/encode for the screenshot tool, with Node's zlib only
 * (no image dependencies). Decodes 8-bit, non-interlaced RGB/RGBA/grey(+alpha)
 * PNGs (what Chrome writes) to RGBA; encodes RGBA.
 */
'use strict';

const zlib = require( 'zlib' );

const SIGNATURE = Buffer.from( [ 137, 80, 78, 71, 13, 10, 26, 10 ] );

const CRC_TABLE = ( () => {
	const table = new Uint32Array( 256 );
	for ( let n = 0; n < 256; n++ ) {
		let c = n;
		for ( let k = 0; k < 8; k++ ) {
			c = c & 1 ? 0xedb88320 ^ ( c >>> 1 ) : c >>> 1;
		}
		table[ n ] = c >>> 0;
	}
	return table;
} )();

const crc32 = ( buffer ) => {
	let c = 0xffffffff;
	for ( let i = 0; i < buffer.length; i++ ) {
		c = CRC_TABLE[ ( c ^ buffer[ i ] ) & 0xff ] ^ ( c >>> 8 );
	}
	return ( c ^ 0xffffffff ) >>> 0;
};

const CHANNELS = { 0: 1, 2: 3, 4: 2, 6: 4 };

const paeth = ( a, b, c ) => {
	const p = a + b - c;
	const pa = Math.abs( p - a );
	const pb = Math.abs( p - b );
	const pc = Math.abs( p - c );
	if ( pa <= pb && pa <= pc ) {
		return a;
	}
	return pb <= pc ? b : c;
};

/** @return {{width:number,height:number,data:Buffer}} RGBA, 4 bytes per pixel. */
function decode( file ) {
	if ( ! file.subarray( 0, 8 ).equals( SIGNATURE ) ) {
		throw new Error( 'Not a PNG' );
	}
	let offset = 8;
	let width, height, bitDepth, colorType, interlace;
	const idat = [];
	while ( offset < file.length ) {
		const length = file.readUInt32BE( offset );
		const type = file.toString( 'ascii', offset + 4, offset + 8 );
		const body = file.subarray( offset + 8, offset + 8 + length );
		if ( type === 'IHDR' ) {
			width = body.readUInt32BE( 0 );
			height = body.readUInt32BE( 4 );
			bitDepth = body[ 8 ];
			colorType = body[ 9 ];
			interlace = body[ 12 ];
		} else if ( type === 'IDAT' ) {
			idat.push( body );
		} else if ( type === 'IEND' ) {
			break;
		}
		offset += 12 + length;
	}
	const channels = CHANNELS[ colorType ];
	if ( bitDepth !== 8 || ! channels || interlace ) {
		throw new Error( `Unsupported PNG (depth ${ bitDepth }, colour type ${ colorType }, interlace ${ interlace })` );
	}
	const raw = zlib.inflateSync( Buffer.concat( idat ) );
	const stride = width * channels;
	const pixels = Buffer.alloc( stride * height );
	for ( let y = 0; y < height; y++ ) {
		const filter = raw[ y * ( stride + 1 ) ];
		const src = y * ( stride + 1 ) + 1;
		const row = y * stride;
		for ( let x = 0; x < stride; x++ ) {
			const left = x >= channels ? pixels[ row + x - channels ] : 0;
			const up = y > 0 ? pixels[ row - stride + x ] : 0;
			const upLeft = y > 0 && x >= channels ? pixels[ row - stride + x - channels ] : 0;
			let value = raw[ src + x ];
			if ( filter === 1 ) {
				value += left;
			} else if ( filter === 2 ) {
				value += up;
			} else if ( filter === 3 ) {
				value += ( left + up ) >> 1;
			} else if ( filter === 4 ) {
				value += paeth( left, up, upLeft );
			}
			pixels[ row + x ] = value & 0xff;
		}
	}
	const data = Buffer.alloc( width * height * 4 );
	for ( let i = 0, j = 0; i < width * height; i++, j += channels ) {
		const grey = channels < 3;
		data[ i * 4 ] = pixels[ j ];
		data[ i * 4 + 1 ] = grey ? pixels[ j ] : pixels[ j + 1 ];
		data[ i * 4 + 2 ] = grey ? pixels[ j ] : pixels[ j + 2 ];
		data[ i * 4 + 3 ] = channels === 4 ? pixels[ j + 3 ] : channels === 2 ? pixels[ j + 1 ] : 255;
	}
	return { width, height, data };
}

const chunk = ( type, body ) => {
	const head = Buffer.alloc( 8 );
	head.writeUInt32BE( body.length, 0 );
	head.write( type, 4, 'ascii' );
	const crc = Buffer.alloc( 4 );
	crc.writeUInt32BE( crc32( Buffer.concat( [ head.subarray( 4 ), body ] ) ), 0 );
	return Buffer.concat( [ head, body, crc ] );
};

/** RGBA buffer to a PNG file buffer. */
function encode( { width, height, data } ) {
	const ihdr = Buffer.alloc( 13 );
	ihdr.writeUInt32BE( width, 0 );
	ihdr.writeUInt32BE( height, 4 );
	ihdr[ 8 ] = 8;
	ihdr[ 9 ] = 6;
	const raw = Buffer.alloc( ( width * 4 + 1 ) * height );
	for ( let y = 0; y < height; y++ ) {
		data.copy( raw, y * ( width * 4 + 1 ) + 1, y * width * 4, ( y + 1 ) * width * 4 );
	}
	return Buffer.concat( [
		SIGNATURE,
		chunk( 'IHDR', ihdr ),
		chunk( 'IDAT', zlib.deflateSync( raw ) ),
		chunk( 'IEND', Buffer.alloc( 0 ) ),
	] );
}

module.exports = { decode, encode };
